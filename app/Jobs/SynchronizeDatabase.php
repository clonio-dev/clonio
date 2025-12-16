<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
use App\Data\SynchronizationOptionsData;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class SynchronizeDatabase implements ShouldBeEncrypted, ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    /**
     * @param  Collection<int, ConnectionData>  $targetConnectionsData
     */
    public function __construct(
        public readonly SynchronizationOptionsData $options,
        public readonly ConnectionData $sourceConnectionData,
        public readonly Collection $targetConnectionsData,
    ) {
        Log::debug("Creating new job instance for connection {$this->sourceConnectionData->name}.");
    }

    public function handle(
        DatabaseInformationRetrievalService $dbInformationRetrievalService,
    ): void {
        Log::debug('Synchronizing database');

        // try to connect to source connection
        Log::debug('Try to connect to source connection');
        try {
            $sourceConnection = $dbInformationRetrievalService->getConnection($this->sourceConnectionData);

            $sourceConnection->getSchemaBuilder();
        } catch (Throwable $exception) {
            Log::error("Failed to connect to database {$this->sourceConnectionData->name}: {$exception->getMessage()}");
            $this->fail($exception);

            return;
        }

        // try to connect to target connections
        Log::debug('Try to connect to target connections');
        $this->targetConnectionsData->each(
            function (ConnectionData $targetConnectionData) use ($dbInformationRetrievalService): void {
                try {
                    $dbInformationRetrievalService->getConnection($targetConnectionData);
                } catch (Throwable $exception) {
                    Log::error("Failed to connect to database {$targetConnectionData->name}: {$exception->getMessage()}");
                    $this->fail($exception);

                    return;
                }

                $this->batch()->add([
                    new CloneSchemaAndPrepareForData(
                        sourceConnectionData: $this->sourceConnectionData,
                        targetConnectionData: $targetConnectionData,
                        synchronizeTableSchemaEnum: $this->options->synchronizeTableSchema,
                        keepUnknownTablesOnTarget: $this->options->keepUnknownTablesOnTarget,
                        migrationTableName: $this->options->migrationTableName,
                        disableForeignKeyConstraints: $this->options->disableForeignKeyConstraints,
                    ),
                    new TransferRecordsForAllTables(
                        sourceConnectionData: $this->sourceConnectionData,
                        targetConnectionData: $targetConnectionData,
                        chunkSize: $this->options->chunkSize,
                        migrationTableName: $this->options->migrationTableName,
                        disableForeignKeyConstraints: $this->options->disableForeignKeyConstraints,
                    ),
                ]);
            }
        );
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return list<object>
     */
    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }
}
