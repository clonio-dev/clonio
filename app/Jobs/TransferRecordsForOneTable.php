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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use stdClass;
use Throwable;

class TransferRecordsForOneTable implements ShouldBeEncrypted, ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public function __construct(
        public readonly ConnectionData $sourceConnectionData,
        public readonly ConnectionData $targetConnectionData,
        public readonly string $tableName,
        public readonly int $chunkSize,
    ) {}

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

        try {
            /** @var Connection $targetConnection */
            $targetConnection = $databaseManager->connectUsing(
                name: $this->targetConnectionData->connectionName(),
                config: $this->targetConnectionData->driver->toArray(),
                force: true,
            );
        } catch (Throwable $exception) {
            Log::error("Failed to connect to database {$this->targetConnectionData->name}: {$exception->getMessage()}");
            $this->fail($exception);

            return;
        }

        if (! $sourceConnection->getSchemaBuilder()->hasTable($this->tableName)
            || ! $targetConnection->getSchemaBuilder()->hasTable($this->tableName)
        ) {
            $exception = new RuntimeException("Table {$this->tableName} does not exist in source or target database.");
            $this->fail($exception);
            throw $exception;
        }

        $table = $sourceConnection->table($this->tableName);

        $orderColumns = $this->getOrderColumns($sourceConnection);
        Log::debug("Order columns for table {$this->tableName}: " . implode(', ', $orderColumns));
        foreach ($orderColumns as $column) {
            $table->orderBy($column);
        }

        $table->chunk(
            $this->chunkSize,
            /**
             * @param  Collection<int, stdClass>  $records
             */
            function (Collection $records, int $page) use ($targetConnection): void {
                Log::info("Transferring {$records->count()} records from {$this->tableName} table.");
                $targetConnection->table($this->tableName)
                    ->insert(
                        $records->map(function (stdClass $record, int $index): array {
                            $record = get_object_vars($record);

                            // mutation
                            if ($this->tableName === 'users') {
                                $record['name'] = value(fn () => fake()->name());
                                $record['email'] = value(fn () => fake()->email());
                                $record['password'] = value(fn (): string => '********');
                            }

                            return $record;
                        })->values()->all()
                    );
            }
        );
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return list<class-string>
     */
    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    /**
     * @return array|mixed|null
     */
    private function getOrderColumns(Connection $sourceConnection): mixed
    {
        $indexes = $sourceConnection->getSchemaBuilder()->getIndexes($this->tableName);
        $orderColumns = collect($indexes)->firstWhere('primary', true)['columns'] ?? [];
        if (count($orderColumns) > 0) {
            return $orderColumns;
        }

        return [$sourceConnection->getSchemaBuilder()->getColumnListing($this->tableName)[0]];
    }
}
