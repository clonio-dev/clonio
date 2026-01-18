<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
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

class TruncateTargetTables implements ShouldBeEncrypted, ShouldQueue
{
    use Batchable, HandlesExceptions, InteractsWithQueue, LogsProcessSteps, Queueable, TransferBatchJob;

    public int $tries = 2;

    public string $tableName = '';

    public function __construct(
        public readonly ConnectionData $sourceConnectionData,
        public readonly ConnectionData $targetConnectionData,
        public readonly TransferRun $run,
    ) {}

    public function handle(
        DatabaseInformationRetrievalService $dbInformationRetrievalService,
    ): void {
        try {
            /** @var Connection $targetConnection */
            $targetConnection = $dbInformationRetrievalService->getConnection($this->targetConnectionData);
            $sourceSchema = $dbInformationRetrievalService->getSchema($this->sourceConnectionData);
            $sourceTableNames = $sourceSchema->getTableListing($sourceSchema->getCurrentSchemaName(), false);

            foreach ($sourceTableNames as $tableName) {
                $this->tableName = $tableName;
                $targetConnection->table($tableName)->delete();
                $this->logSuccess('table_emptied', "All rows on table {$tableName} deleted on target database.");
            }
        } catch (QueryException $e) {
            $this->handleQueryException($e);
        } catch (PDOException $e) {
            $this->handleConnectionException($e);
        } catch (Throwable $e) {
            $this->handleUnexpectedException($e);
        }
    }
}
