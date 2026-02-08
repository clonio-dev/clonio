<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
use App\Data\RowSelectionStrategyEnum;
use App\Data\TableAnonymizationOptionsData;
use App\Data\TableRowSelectionData;
use App\Jobs\Concerns\ClassifiesError;
use App\Jobs\Concerns\HandlesExceptions;
use App\Jobs\Concerns\LogsProcessSteps;
use App\Jobs\Concerns\TransferBatchJob;
use App\Models\CloningRun;
use App\Services\AnonymizationService;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Sleep;
use PDOException;
use RuntimeException;
use stdClass;
use Throwable;

class TransferRecordsForOneTable implements ShouldBeEncrypted, ShouldQueue
{
    use Batchable, ClassifiesError, HandlesExceptions, InteractsWithQueue, LogsProcessSteps, Queueable, TransferBatchJob;

    public int $tries = 1;

    /**
     * @param  array<int, array{column: string, values: array<int, mixed>}>|null  $foreignKeyFilters  FK filters for child tables referencing row-limited parents
     */
    public function __construct(
        public readonly ConnectionData $sourceConnectionData,
        public readonly ConnectionData $targetConnectionData,
        public readonly string $tableName,
        public readonly int $chunkSize,
        public readonly CloningRun $run,
        public readonly bool $disableForeignKeyConstraints = false,
        public readonly ?TableAnonymizationOptionsData $tableAnonymizationOptions = null,
        public readonly ?array $foreignKeyFilters = null,
    ) {}

    public function handle(
        DatabaseInformationRetrievalService $dbInformationRetrievalService,
        AnonymizationService $anonymizationService,
    ): void {
        $this->logInfo('table_started', "Starting table copy process for {$this->tableName} table");

        try {
            $sourceConnection = $dbInformationRetrievalService->getConnection($this->sourceConnectionData);

            $sourceTable = $dbInformationRetrievalService
                ->withConnectionForTable($this->sourceConnectionData, $this->tableName);

            /** @var Connection $targetConnection */
            $targetConnection = $dbInformationRetrievalService->getConnection($this->targetConnectionData);
        } catch (QueryException $e) {
            $this->handleQueryException($e);
        } catch (PDOException $e) {
            $this->handleConnectionException($e);
        } catch (Throwable $e) {
            $this->handleUnexpectedException($e);
        }

        assert($sourceConnection instanceof Connection);
        assert($targetConnection instanceof Connection);

        if (! $sourceConnection->getSchemaBuilder()->hasTable($this->tableName)
            || ! $targetConnection->getSchemaBuilder()->hasTable($this->tableName)
        ) {
            $exception = new RuntimeException("Table {$this->tableName} does not exist in source or target database.");
            $this->fail($exception);
            throw $exception;
        }

        if ($this->disableForeignKeyConstraints) {
            $this->logDebug('foreign_keys', 'Disabling foreign key constraints on target database');
            $targetConnection->getSchemaBuilder()->disableForeignKeyConstraints();
        }

        $query = $sourceTable->query();

        $orderColumns = $sourceTable->orderColumns();
        $this->logDebug('order_columns', "Order columns for table {$this->tableName}: " . implode(', ', $orderColumns));
        foreach ($orderColumns as $column) {
            $query->orderBy($column);
        }

        // Apply FK filters for child tables referencing row-limited parents
        if ($this->foreignKeyFilters) {
            foreach ($this->foreignKeyFilters as $filter) {
                $query->whereIn($filter['column'], $filter['values']);
            }
            $this->logDebug('fk_filters', 'Applied ' . count($this->foreignKeyFilters) . ' FK filter(s)');
        }

        // Determine if row selection limits apply
        $rowSelection = $this->tableAnonymizationOptions?->rowSelection;
        $hasRowLimit = $rowSelection instanceof TableRowSelectionData
            && $rowSelection->strategy !== RowSelectionStrategyEnum::FullTable;

        if ($hasRowLimit) {
            $sortColumn = $rowSelection->sortColumn ?? ($orderColumns[0] ?? null);
            if ($sortColumn) {
                $query->reorder();
                $direction = $rowSelection->strategy === RowSelectionStrategyEnum::FirstX ? 'asc' : 'desc';
                $query->orderBy($sortColumn, $direction);
            }
            $query->limit($rowSelection->limit);
            $this->logDebug('row_selection', "Applying {$rowSelection->strategy->value}: {$rowSelection->limit} rows ordered by {$sortColumn}");
        }

        $totalRows = 0;
        $failedChunks = 0;
        $maxChunkRetries = 3;
        $startTime = microtime(true);

        try {
            if ($hasRowLimit) {
                // Laravel's chunk() is incompatible with limit(), so fetch all at once and chunk manually
                $allRecords = $query->get();
                $totalRowCount = $allRecords->count();

                $this->logInfo(
                    'data_copy_started',
                    "Starting data copy of {$totalRowCount} rows with row selection (chunk size: {$this->chunkSize})"
                );

                $page = 0;
                foreach ($allRecords->chunk($this->chunkSize) as $records) {
                    $page++;
                    $this->processChunk(
                        $records, $targetConnection, $totalRows, $failedChunks,
                        $maxChunkRetries, $anonymizationService, $totalRowCount, $startTime,
                    );
                }
            } else {
                $totalRowCount = $sourceTable->query()->count();

                // Apply FK filters to count query as well
                if ($this->foreignKeyFilters) {
                    $countQuery = $sourceTable->query();
                    foreach ($this->foreignKeyFilters as $filter) {
                        $countQuery->whereIn($filter['column'], $filter['values']);
                    }
                    $totalRowCount = $countQuery->count();
                }

                $this->logInfo(
                    'data_copy_started',
                    "Starting chunked data copy of {$totalRowCount} rows (chunk size: {$this->chunkSize})"
                );

                $query->chunk(
                    $this->chunkSize,
                    /**
                     * @param  Collection<int, stdClass>  $records
                     */
                    function (Collection $records, int $page) use (
                        $targetConnection,
                        &$totalRows,
                        &$failedChunks,
                        $maxChunkRetries,
                        $anonymizationService,
                        $totalRowCount,
                        $startTime
                    ): void {
                        $this->processChunk(
                            $records, $targetConnection, $totalRows, $failedChunks,
                            $maxChunkRetries, $anonymizationService, $totalRowCount, $startTime,
                        );
                    }
                );
            }

            $this->logSuccess(
                'data_copy_completed',
                "Data copy completed. Total rows: {$totalRows}, Failed chunks: {$failedChunks}",
                data: [
                    'rows_processed' => $totalRows,
                    'failed_chunks' => $failedChunks,
                    'duration_seconds' => microtime(true) - $startTime,
                ],
            );

            return;
        } catch (QueryException $e) {
            if ($this->isPermissionError($e)) {
                $this->logError(
                    'data_read_permission_denied',
                    "Insufficient permissions to read from {$this->tableName}: {$e->getMessage()}"
                );

                throw new RuntimeException("Insufficient permissions to read from table {$this->tableName}. " .
                    'Please grant SELECT privilege to the database user.', $e->getCode(), previous: $e);
            }

            // Tabelle existiert nicht
            if ($this->isTableNotFoundError($e)) {
                $this->logError(
                    'table_not_found',
                    "Table {$this->tableName} does not exist in source database: {$e->getMessage()}"
                );

                throw new RuntimeException("Table {$this->tableName} does not exist in source database. " .
                    'Please check the table name in your configuration.', $e->getCode(), previous: $e);
            }

            throw $e;
        } finally {
            if ($this->disableForeignKeyConstraints) {
                $this->logDebug('foreign_keys', 'Enabling foreign key constraints on target database');
                $targetConnection->getSchemaBuilder()->enableForeignKeyConstraints();
            }
        }
    }

    /**
     * Process a single chunk of records: anonymize, insert into target, and log progress.
     *
     * @param  Collection<int, stdClass>  $records
     */
    private function processChunk(
        Collection $records,
        Connection $targetConnection,
        int &$totalRows,
        int &$failedChunks,
        int $maxChunkRetries,
        AnonymizationService $anonymizationService,
        int $totalRowCount,
        float $startTime,
    ): void {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $retryCount = 0;

        while ($retryCount < $maxChunkRetries) {
            try {
                $rowsArray = $records->map(function (object $record) use ($anonymizationService): array {
                    $record = get_object_vars($record);

                    return $anonymizationService->anonymizeRecord($record, $this->tableAnonymizationOptions);
                })->values()->all();

                $targetConnection
                    ->table($this->tableName)
                    ->insert($rowsArray);

                $totalRows += $records->count();

                $percent = $totalRowCount > 0 ? (int) round(($totalRows / $totalRowCount) * 100) : 100;
                $elapsedSeconds = microtime(true) - $startTime;
                $rowsPerSecond = $elapsedSeconds > 0 ? $totalRows / $elapsedSeconds : 0;
                $remainingRows = $totalRowCount - $totalRows;
                $estimatedSecondsRemaining = $rowsPerSecond > 0 ? (int) ceil($remainingRows / $rowsPerSecond) : null;

                $this->logProgress(
                    'table_transfer_progress',
                    "Transferred {$totalRows} / {$totalRowCount} rows",
                    [
                        'rows_processed' => $totalRows,
                        'total_rows' => $totalRowCount,
                        'percent' => $percent,
                        'rows_per_second' => (int) round($rowsPerSecond),
                        'elapsed_seconds' => (int) round($elapsedSeconds),
                        'estimated_seconds_remaining' => $estimatedSecondsRemaining,
                    ]
                );

                break;

            } catch (QueryException $e) {
                $retryCount++;

                if ($this->isTemporaryError($e) && $retryCount < $maxChunkRetries) {
                    $this->logWarning(
                        'chunk_retry',
                        "Chunk failed (attempt {$retryCount}/{$maxChunkRetries}), retrying: {$e->getMessage()}"
                    );

                    Sleep::sleep(2 * $retryCount);

                    continue;
                }

                $failedChunks++;

                $this->logError(
                    'chunk_failed',
                    "Chunk permanently failed after {$retryCount} retries: {$e->getMessage()}"
                );

                throw $e;
            }
        }
    }
}
