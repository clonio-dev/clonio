<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
use App\Jobs\Concerns\HandlesExceptions;
use App\Jobs\Concerns\LogsProcessSteps;
use App\Jobs\Concerns\TransferBatchJob;
use App\Models\CloningRun;
use App\Services\DatabaseInformationRetrievalService;
use App\Services\SchemaReplicator;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Queue\InteractsWithQueue;
use PDOException;
use Throwable;

class CloneSchema implements ShouldBeEncrypted, ShouldQueue
{
    use Batchable, HandlesExceptions, InteractsWithQueue, LogsProcessSteps, Queueable, TransferBatchJob;

    public int $tries = 2;

    public string $tableName = '';

    public function __construct(
        public readonly ConnectionData $sourceConnectionData,
        public readonly ConnectionData $targetConnectionData,
        public readonly array $tables,
        public readonly CloningRun $run,
    ) {}

    public function handle(
        DatabaseInformationRetrievalService $dbInformationRetrievalService,
        SchemaReplicator $schemaReplicator,
    ): void {
        $this->logDebug('phase_started', 'Starting clone schema phase');

        try {
            /** @var Connection $sourceConnection */
            $sourceConnection = $dbInformationRetrievalService->getConnection($this->sourceConnectionData);
            /** @var Connection $targetConnection */
            $targetConnection = $dbInformationRetrievalService->getConnection($this->targetConnectionData);

            $schemaReplicator->replicateDatabase(
                source: $sourceConnection,
                target: $targetConnection,
                tables: $this->tables,
                visitor: function (string $tableName, string $event, string $message, string $level = 'info'): void {
                    $this->tableName = $tableName;
                    $this->log($level, $event, $message);
                },
            );
        } catch (QueryException $e) {
            $this->handleQueryException($e);
        } catch (PDOException $e) {
            $this->handleConnectionException($e);
        } catch (Throwable $e) {
            $this->handleUnexpectedException($e);
        }
    }
}
