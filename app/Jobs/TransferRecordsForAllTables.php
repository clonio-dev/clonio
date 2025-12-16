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
use Illuminate\Support\Facades\Log;
use Throwable;

class TransferRecordsForAllTables implements ShouldBeEncrypted, ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public function __construct(
        public readonly ConnectionData $sourceConnectionData,
        public readonly ConnectionData $targetConnectionData,
        public readonly SynchronizationOptionsData $options,
    ) {}

    public function handle(
        DatabaseInformationRetrievalService $dbInformationRetrievalService,
    ): void {
        try {
            $tableNames = $dbInformationRetrievalService->getTableNames($this->sourceConnectionData);
        } catch (Throwable $exception) {
            Log::error("Failed to connect to database {$this->sourceConnectionData->name}: {$exception->getMessage()}");
            $this->fail($exception);

            return;
        }

        foreach ($tableNames as $tableName) {
            if ($this->options->migrationTableName !== null && $tableName === $this->options->migrationTableName) {
                Log::info("Migration table {$tableName} will not be transferred again. Skipping.");

                continue;
            }

            $recordCount = $dbInformationRetrievalService
                ->withConnectionForTable($this->sourceConnectionData, $tableName)
                ->recordCount();
            Log::info("Transferring {$recordCount} records from {$tableName} table.");

            if ($recordCount > 0) {
                $batch = $this->batch();
                assert($batch !== null);
                $batch->add(
                    new TransferRecordsForOneTable(
                        sourceConnectionData: $this->sourceConnectionData,
                        targetConnectionData: $this->targetConnectionData,
                        tableName: $tableName,
                        chunkSize: $this->options->chunkSize,
                        disableForeignKeyConstraints: $this->options->disableForeignKeyConstraints,
                        tableAnonymizationOptions: $this->options->getAnonymizationOptionsForTable($tableName),
                    )
                );
            }
        }
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
