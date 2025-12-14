<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
use App\Data\SynchronizationOptionsData;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class SynchronizeDatabase implements ShouldQueue, ShouldBeEncrypted
{
    use Batchable, Queueable, InteractsWithQueue;

    public int $tries = 2;

    /**
     * @param \Illuminate\Support\Collection<ConnectionData> $targetConnectionsData
     */
    public function __construct(
        public readonly SynchronizationOptionsData $options,
        public readonly ConnectionData $sourceConnectionData,
        public readonly Collection $targetConnectionsData,
    ) {
        Log::debug("Creating new job instance for connection {$this->sourceConnectionData->name}.");
    }

    public function handle(
        DatabaseManager $databaseManager,
    ): void {
        Log::debug('Synchronizing database');

        // try to connect to source connection
        Log::debug('Try to connect to source connection');
        try {
            $sourceConnection = $databaseManager->connectUsing(
                name: $this->sourceConnectionData->connectionName(),
                config: $this->sourceConnectionData->driver->toArray(),
                force: true,
            );

            $sourceSchema = $sourceConnection->getSchemaBuilder();
        } catch (Throwable $exception) {
            Log::error("Failed to connect to database {$this->sourceConnectionData->name}: {$exception->getMessage()}");
            $this->fail($exception);
            return;
        }

        // try to connect to target connections
        Log::debug('Try to connect to target connections');
        $this->targetConnectionsData->each(
            function (ConnectionData $targetConnectionData) use ($databaseManager) {
                try {
                    $databaseManager->connectUsing(
                        name: $targetConnectionData->connectionName(),
                        config: $targetConnectionData->driver->toArray(),
                        force: true,
                    );
                } catch (Throwable $exception) {
                    Log::error("Failed to connect to database {$this->sourceConnectionData->name}: {$exception->getMessage()}");
                    $this->fail($exception);
                    return;
                }

                if ($this->options->disableForeignKeyConstraints) {
                    $this->batch()->add(new DisableForeignKeyConstraintsOnSchema($targetConnectionData));
                }

                $this->batch()->add([
                    new CloneSchemaAndPrepareForData(
                        sourceConnectionData: $this->sourceConnectionData,
                        targetConnectionData: $targetConnectionData,
                        synchronizeTableSchemaEnum: $this->options->synchronizeTableSchema,
                        keepUnknownTablesOnTarget: $this->options->keepUnknownTablesOnTarget,
                        migrationTableName: $this->options->migrationTableName,
                    ),
                    new TransferRecordsForAllTables(
                        sourceConnectionData: $this->sourceConnectionData,
                        targetConnectionData: $targetConnectionData,
                        chunkSize: $this->options->chunkSize,
                        migrationTableName: $this->options->migrationTableName,
                    ),
                ]);

                if ($this->options->disableForeignKeyConstraints) {
//                    $this->batch()->add(new EnableForeignKeyConstraintsOnSchema($targetConnectionData));
                }
            }
        );
    }

    /**
     * Get the middleware the job should pass through.
     * @return list<class-string>
     */
    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }
}
