<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\Log;
use Throwable;

class TransferRecordsForAllTables implements ShouldQueue, ShouldBeEncrypted
{
    use Batchable, Queueable, InteractsWithQueue;

    public int $tries = 2;

    public function __construct(
        public readonly ConnectionData $sourceConnectionData,
        public readonly ConnectionData $targetConnectionData,
        public readonly int $chunkSize,
        public ?string $migrationTableName,
    ) {
    }

    public function handle(DatabaseManager $databaseManager): void
    {
        try {
            /** @var Connection $sourceConnection */
            $sourceConnection = $databaseManager->connectUsing(
                name: $this->sourceConnectionData->connectionName(),
                config: $this->sourceConnectionData->driver->toArray(),
                force: true,
            );
        } catch (Throwable $exception) {
            Log::error("Failed to connect to database {$this->sourceConnectionData->name}: {$exception->getMessage()}");
            $this->fail($exception);
            return;
        }

        $tableNames = $sourceConnection->getSchemaBuilder()->getTableListing(schemaQualified: false);
        foreach ($tableNames as $tableName) {
            if ($this->migrationTableName !== null && $tableName === $this->migrationTableName) {
                Log::info("Migration table {$tableName} will not be transferred again. Skipping.");
                continue;
            }

            $recordCount = $sourceConnection->table($tableName)->count();
            Log::info("Transferring {$recordCount} records from {$tableName} table.");

            if ($recordCount > 0) {
                $this->batch()->add(
                    new TransferRecordsForOneTable(
                        sourceConnectionData: $this->sourceConnectionData,
                        targetConnectionData: $this->targetConnectionData,
                        tableName: $tableName,
                        chunkSize: $this->chunkSize,
                    )
                );
            }
        }
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
