<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
use App\Data\TableAnonymizationOptionsData;
use App\Services\AnonymizationService;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
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
        public readonly bool $disableForeignKeyConstraints = false,
        public readonly ?TableAnonymizationOptionsData $tableAnonymizationOptions = null,
    ) {}

    public function handle(
        DatabaseInformationRetrievalService $dbInformationRetrievalService,
        AnonymizationService $anonymizationService,
    ): void {
        try {
            $sourceConnection = $dbInformationRetrievalService->getConnection($this->sourceConnectionData);

            $sourceTable = $dbInformationRetrievalService
                ->withConnectionForTable($this->sourceConnectionData, $this->tableName);
        } catch (Throwable $exception) {
            Log::error($exception->getMessage());
            $this->fail($exception);

            return;
        }

        try {
            /** @var Connection $targetConnection */
            $targetConnection = $dbInformationRetrievalService->getConnection($this->targetConnectionData);
        } catch (Throwable $exception) {
            Log::error("Failed to connect to database {$this->targetConnectionData->name}: {$exception->getMessage()}");
            $this->fail($exception);

            return;
        }

        assert($sourceConnection instanceof Connection);
        if (! $sourceConnection->getSchemaBuilder()->hasTable($this->tableName)
            || ! $targetConnection->getSchemaBuilder()->hasTable($this->tableName)
        ) {
            $exception = new RuntimeException("Table {$this->tableName} does not exist in source or target database.");
            $this->fail($exception);
            throw $exception;
        }

        if ($this->disableForeignKeyConstraints) {
            Log::debug('Disabling foreign key constraints on target database.');
            $targetConnection->getSchemaBuilder()->disableForeignKeyConstraints();
        }

        $query = $sourceTable->query();

        $orderColumns = $sourceTable->orderColumns();
        Log::debug("Order columns for table {$this->tableName}: " . implode(', ', $orderColumns));
        foreach ($orderColumns as $column) {
            $query->orderBy($column);
        }

        $query->chunk(
            $this->chunkSize,
            /**
             * @param  Collection<int, stdClass>  $records
             */
            function (Collection $records, int $page) use ($targetConnection, $anonymizationService): void {
                Log::info("Transferring {$records->count()} records from {$this->tableName} table.");
                $targetConnection->table($this->tableName)
                    ->insert(
                        $records->map(function (object $record, int $index) use ($anonymizationService): array {
                            assert($record instanceof stdClass);
                            $record = get_object_vars($record);

                            return $anonymizationService->anonymizeRecord($record, $this->tableAnonymizationOptions);
                        })->values()->all()
                    );
            }
        );

        if ($this->disableForeignKeyConstraints) {
            Log::debug('Enabling foreign key constraints on target database.');
            $targetConnection->getSchemaBuilder()->enableForeignKeyConstraints();
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
