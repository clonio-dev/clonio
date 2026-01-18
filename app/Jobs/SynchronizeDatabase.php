<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
use App\Data\SynchronizationOptionsData;
use App\Jobs\Concerns\LogsProcessSteps;
use App\Jobs\Concerns\TransferBatchJob;
use App\Models\TransferRun;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class SynchronizeDatabase implements ShouldBeEncrypted, ShouldQueue
{
    use Batchable, InteractsWithQueue, LogsProcessSteps, Queueable, TransferBatchJob;

    public int $tries = 2;

    public string $tableName = '';

    public function __construct(
        public readonly SynchronizationOptionsData $options,
        public readonly ConnectionData $sourceConnectionData,
        public readonly ConnectionData $targetConnectionData,
        public readonly TransferRun $run,
    ) {}

    public function handle(
        DatabaseInformationRetrievalService $dbInformationRetrievalService,
    ): void {
        try {
            $sourceConnection = $dbInformationRetrievalService->getConnection($this->sourceConnectionData);

            assert($sourceConnection instanceof \Illuminate\Database\Connection);
            $sourceConnection->getSchemaBuilder();
        } catch (Throwable $exception) {
            $this->logError('connection_failed', "Failed to connect to database {$this->sourceConnectionData->name}: {$exception->getMessage()}");
            $this->fail($exception);

            return;
        }

        try {
            $dbInformationRetrievalService->getConnection($this->targetConnectionData);
        } catch (Throwable $exception) {
            $this->logError('connection_failed', "Failed to connect to database {$this->targetConnectionData->name}: {$exception->getMessage()}");
            $this->fail($exception);

            return;
        }

        $batch = $this->batch();
        assert($batch !== null);
        $this->logInfo('synchronization_started', "Starting database synchronization.");

        $batch->add([
            new CloneSchemaAndPrepareForData(
                sourceConnectionData: $this->sourceConnectionData,
                targetConnectionData: $this->targetConnectionData,
                synchronizeTableSchemaEnum: $this->options->synchronizeTableSchema,
                keepUnknownTablesOnTarget: $this->options->keepUnknownTablesOnTarget,
                migrationTableName: $this->options->migrationTableName,
                run: $this->run,
                disableForeignKeyConstraints: $this->options->disableForeignKeyConstraints,
            ),
            new TransferRecordsForAllTables(
                sourceConnectionData: $this->sourceConnectionData,
                targetConnectionData: $this->targetConnectionData,
                options: $this->options,
                run: $this->run,
            ),
        ]);
    }
}
