<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
use App\Data\SynchronizationOptionsData;
use App\Jobs\Concerns\LogsProcessSteps;
use App\Jobs\Concerns\TransferBatchJob;
use App\Models\CloningRun;
use App\Services\DatabaseInformationRetrievalService;
use App\Services\DependencyResolver;
use App\Services\SchemaInspector\SchemaInspectorFactory;
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
        public readonly CloningRun $run,
    ) {}

    public function handle(
        DatabaseInformationRetrievalService $dbInformationRetrievalService,
        DependencyResolver $dependencyResolver,
    ): void {
        try {
            $sourceConnection = $dbInformationRetrievalService->getConnection($this->sourceConnectionData);

            assert($sourceConnection instanceof \Illuminate\Database\Connection);
            $sourceConnection->getSchemaBuilder();
        } catch (Throwable $exception) {
            $this->logError('connection_failed', "Failed to connect to database `{$this->sourceConnectionData->name}`");
            $this->logErrorMessage($exception->getMessage(), $dbInformationRetrievalService->connectionMap());
            $this->fail($exception);

            return;
        }

        try {
            $dbInformationRetrievalService->getConnection($this->targetConnectionData);
        } catch (Throwable $exception) {
            $this->logError('connection_failed', "Failed to connect to database `{$this->targetConnectionData->name}`");
            $this->logErrorMessage($exception->getMessage(), $dbInformationRetrievalService->connectionMap());
            $this->fail($exception);

            return;
        }

        $batch = $this->batch();
        assert($batch !== null);
        $this->logInfo('synchronization_started', 'Starting database synchronization');

        $sourceInspector = SchemaInspectorFactory::create($sourceConnection);
        $sourceSchema = $sourceInspector->getDatabaseSchema($sourceConnection);
        $tableNames = $sourceSchema->getTableNames()->all();

        $order = $dependencyResolver->getProcessingOrder($tableNames, $sourceConnection);

        $batch->add([
            new CloneSchema(
                sourceConnectionData: $this->sourceConnectionData,
                targetConnectionData: $this->targetConnectionData,
                tables: $order['insert_order'],
                run: $this->run,
            ),
            new TruncateTargetTables(
                sourceConnectionData: $this->sourceConnectionData,
                targetConnectionData: $this->targetConnectionData,
                tables: $order['delete_order'],
                run: $this->run,
            ),
        ]);

        if (! $this->options->keepUnknownTablesOnTarget) {
            $batch->add(
                new DropUnknownTables(
                    sourceConnectionData: $this->sourceConnectionData,
                    targetConnectionData: $this->targetConnectionData,
                    run: $this->run,
                )
            );
        }

        $batch->add(
            new TransferRecordsForAllTables(
                sourceConnectionData: $this->sourceConnectionData,
                targetConnectionData: $this->targetConnectionData,
                options: $this->options,
                run: $this->run,
            ),
        );
    }
}
