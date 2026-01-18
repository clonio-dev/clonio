<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
use App\Data\SynchronizeTableSchemaEnum;
use App\Jobs\Concerns\HandlesExceptions;
use App\Jobs\Concerns\LogsProcessSteps;
use App\Jobs\Concerns\TransferBatchJob;
use App\Models\TransferRun;
use App\Services\DatabaseInformationRetrievalService;
use App\Services\SchemaReplicator;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Builder;
use Illuminate\Queue\InteractsWithQueue;
use PDOException;
use Throwable;

class CloneSchemaAndPrepareForData implements ShouldBeEncrypted, ShouldQueue
{
    use Batchable, HandlesExceptions, InteractsWithQueue, LogsProcessSteps, Queueable, TransferBatchJob;

    public int $tries = 2;

    public string $tableName = '';

    public function __construct(
        public readonly ConnectionData $sourceConnectionData,
        public readonly ConnectionData $targetConnectionData,
        public readonly SynchronizeTableSchemaEnum $synchronizeTableSchemaEnum,
        public readonly bool $keepUnknownTablesOnTarget,
        public readonly ?string $migrationTableName,
        public readonly TransferRun $run,
        public bool $disableForeignKeyConstraints = false,
    ) {}

    public function handle(
        DatabaseInformationRetrievalService $dbInformationRetrievalService,
        SchemaReplicator $schemaReplicator,
    ): void {
        try {
            /** @var Connection $sourceConnection */
            $sourceConnection = $dbInformationRetrievalService->getConnection($this->sourceConnectionData);
            /** @var Connection $targetConnection */
            $targetConnection = $dbInformationRetrievalService->getConnection($this->targetConnectionData);

            $schemaReplicator->replicateDatabase($sourceConnection, $targetConnection, function (string $tableName, string $event, string $message): void {
                $this->tableName = $tableName;
                $this->logInfo($event, $message);
            });

            $sourceSchema = $dbInformationRetrievalService->getSchema($this->sourceConnectionData);
            $targetSchema = $dbInformationRetrievalService->getSchema($this->targetConnectionData);
            $this->processUnknownTablesOnTargetWhenNecessary($sourceSchema, $targetSchema);
            $this->truncateTablesOnTargetWhenNecessary($sourceSchema, $targetConnection);
        } catch (QueryException $e) {
            $this->handleQueryException($e);
        } catch (PDOException $e) {
            $this->handleConnectionException($e);
        } catch (Throwable $e) {
            $this->handleUnexpectedException($e);
        }
    }

    private function processUnknownTablesOnTargetWhenNecessary(Builder $sourceSchema, Builder $targetSchema): void
    {
        if ($this->keepUnknownTablesOnTarget) {
            return;
        }

        $sourceTableNames = $sourceSchema->getTableListing();
        $targetTableNames = $targetSchema->getTableListing();

        $unknownTableNames = array_diff($targetTableNames, $sourceTableNames);
        foreach ($unknownTableNames as $unknownTableName) {
            $this->tableName = $unknownTableName;
            $targetSchema->drop($unknownTableName);
            $this->logSuccess('table_dropped', "Dropped table {$unknownTableName} from target database.");
        }
    }

    private function truncateTablesOnTargetWhenNecessary(Builder $sourceSchema, Connection $targetConnection): void
    {
        if ($this->synchronizeTableSchemaEnum !== SynchronizeTableSchemaEnum::TRUNCATE) {
            return;
        }

        $sourceTableNames = $sourceSchema->getTableListing();
        foreach ($sourceTableNames as $tableName) {
            $this->tableName = $tableName;
            $targetConnection->table($tableName)->delete();
            $this->logSuccess('table_emptied', "All rows on table {$tableName} deleted on target database.");
        }
    }
}
