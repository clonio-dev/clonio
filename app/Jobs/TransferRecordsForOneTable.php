<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
use App\Data\KeyRemappingConfigData;
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
use App\Services\KeyMappingRepository;
use App\Services\KeyRemappingService;
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
    use Batchable;
    use ClassifiesError;
    use HandlesExceptions;
    use InteractsWithQueue;
    use LogsProcessSteps;
    use Queueable;
    use TransferBatchJob;

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
        public readonly ?KeyRemappingConfigData $keyRemappingConfig = null,
    ) {}

    public function handle(
        DatabaseInformationRetrievalService $dbInformationRetrievalService,
        AnonymizationService $anonymizationService,
        KeyRemappingService $keyRemappingService,
        KeyMappingRepository $keyMappingRepository,
    ): void {
        $this->logInfo('table_started', sprintf('Starting table copy process for %s table', $this->tableName));

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
            $exception = new RuntimeException(sprintf('Table %s does not exist in source or target database.', $this->tableName));
            $this->fail($exception);
            throw $exception;
        }

        if ($this->disableForeignKeyConstraints) {
            $this->logDebug('foreign_keys', 'Disabling foreign key constraints on target database');
            $targetConnection->getSchemaBuilder()->disableForeignKeyConstraints();
        }

        $query = $sourceTable->query();

        $orderColumns = $sourceTable->orderColumns();
        $this->logDebug('order_columns', sprintf('Order columns for table %s: ', $this->tableName) . implode(', ', $orderColumns));
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
            $this->logDebug('row_selection', sprintf('Applying %s: %d rows ordered by %s', $rowSelection->strategy->value, $rowSelection->limit, $sortColumn));
        }

        $totalRows = 0;
        $failedChunks = 0;
        $skippedRows = 0;
        $maxChunkRetries = 3;
        $startTime = microtime(true);

        try {
            if ($hasRowLimit) {
                // Laravel's chunk() is incompatible with limit(), so fetch all at once and chunk manually
                $allRecords = $query->get();
                $totalRowCount = $allRecords->count();

                $this->logInfo(
                    'data_copy_started',
                    sprintf('Starting data copy of %d rows with row selection (chunk size: %d)', $totalRowCount, $this->chunkSize)
                );

                $page = 0;
                foreach ($allRecords->chunk($this->chunkSize) as $records) {
                    $page++;
                    $this->processChunk(
                        $records, $targetConnection, $totalRows, $failedChunks,
                        $maxChunkRetries, $anonymizationService, $keyRemappingService, $keyMappingRepository, $totalRowCount, $startTime, $skippedRows,
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
                    sprintf('Starting chunked data copy of %d rows (chunk size: %d)', $totalRowCount, $this->chunkSize)
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
                        &$skippedRows,
                        $maxChunkRetries,
                        $anonymizationService,
                        $keyRemappingService,
                        $keyMappingRepository,
                        $totalRowCount,
                        $startTime
                    ): void {
                        $this->processChunk(
                            $records, $targetConnection, $totalRows, $failedChunks,
                            $maxChunkRetries, $anonymizationService, $keyRemappingService, $keyMappingRepository, $totalRowCount, $startTime, $skippedRows,
                        );
                    }
                );
            }

            $this->logSuccess(
                'data_copy_completed',
                sprintf('Data copy completed. Total rows: %d, Skipped: %d, Failed chunks: %d', $totalRows, $skippedRows, $failedChunks),
                data: [
                    'rows_processed' => $totalRows,
                    'rows_skipped' => $skippedRows,
                    'failed_chunks' => $failedChunks,
                    'duration_seconds' => microtime(true) - $startTime,
                ],
            );

            return;
        } catch (QueryException $queryException) {
            if ($this->isPermissionError($queryException)) {
                $this->logError(
                    'data_read_permission_denied',
                    sprintf('Insufficient permissions to read from %s: %s', $this->tableName, $queryException->getMessage())
                );

                throw new RuntimeException(sprintf('Insufficient permissions to read from table %s. ', $this->tableName) .
                    'Please grant SELECT privilege to the database user.', $queryException->getCode(), previous: $queryException);
            }

            // Tabelle existiert nicht
            if ($this->isTableNotFoundError($queryException)) {
                $this->logError(
                    'table_not_found',
                    sprintf('Table %s does not exist in source database: %s', $this->tableName, $queryException->getMessage())
                );

                throw new RuntimeException(sprintf('Table %s does not exist in source database. ', $this->tableName) .
                    'Please check the table name in your configuration.', $queryException->getCode(), previous: $queryException);
            }

            throw $queryException;
        } finally {
            if ($this->disableForeignKeyConstraints) {
                $this->logDebug('foreign_keys', 'Enabling foreign key constraints on target database');
                $targetConnection->getSchemaBuilder()->enableForeignKeyConstraints();
            }
        }
    }

    /**
     * Process a single chunk of records: anonymize, remap IDs, insert into target, and log progress.
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
        KeyRemappingService $keyRemappingService,
        KeyMappingRepository $keyMappingRepository,
        int $totalRowCount,
        float $startTime,
        int &$skippedRows = 0,
    ): void {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $retryCount = 0;

        while ($retryCount < $maxChunkRetries) {
            try {
                $unmappedFkCount = 0;
                /** @var array<int, array{original: array<string, mixed>, mapped: array<string, mixed>}> $rowPairs */
                $rowPairs = $records->map(function (object $record) use ($anonymizationService, $keyRemappingService, &$unmappedFkCount): array {
                    $original = get_object_vars($record);
                    $mapped = $anonymizationService->anonymizeRecord($original, $this->tableAnonymizationOptions);

                    if ($this->keyRemappingConfig?->enabled) {
                        $result = $keyRemappingService->applyMapping(
                            $mapped,
                            $this->keyRemappingConfig,
                            $this->tableName,
                            $this->run,
                        );
                        $mapped = $result['row'];
                        $unmappedFkCount += $result['unmappedFks'];
                    }

                    return ['original' => $original, 'mapped' => $mapped];
                })->values()->all();
                $rowsArray = array_column($rowPairs, 'mapped');

                if ($unmappedFkCount > 0) {
                    $this->logWarning('unmapped_fk_value', sprintf('Found %d unmapped FK value(s) in table %s', $unmappedFkCount, $this->tableName), [
                        'table' => $this->tableName,
                        'count' => $unmappedFkCount,
                    ]);
                }

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
                    sprintf('Transferred %d / %d rows', $totalRows, $totalRowCount),
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
                        sprintf('Chunk failed (attempt %d/%d), retrying: %s', $retryCount, $maxChunkRetries, $e->getMessage())
                    );

                    Sleep::sleep(2 * $retryCount);

                    continue;
                }

                if ($this->isUniqueConstraintError($e) || $this->isForeignKeyViolationError($e)) {
                    // Fall back to row-by-row insert, skipping rows that violate constraints
                    $chunkSkipped = 0;
                    $pkColumn = $this->keyRemappingConfig?->getTableConfig($this->tableName)?->primaryKey;

                    foreach ($rowPairs as $pair) {
                        try {
                            $targetConnection->table($this->tableName)->insert([$pair['mapped']]);
                        } catch (QueryException $rowException) {
                            if ($this->isUniqueConstraintError($rowException) || $this->isForeignKeyViolationError($rowException)) {
                                $chunkSkipped++;

                                // Remove stale PK mapping so downstream FK remappings don't point to a ghost ID
                                if ($pkColumn !== null && isset($pair['original'][$pkColumn])) {
                                    $keyMappingRepository->deleteMapping(
                                        $this->run,
                                        $this->tableName,
                                        $pkColumn,
                                        $pair['original'][$pkColumn],
                                    );
                                }
                            } else {
                                throw $rowException;
                            }
                        }
                    }

                    $inserted = count($rowsArray) - $chunkSkipped;
                    $totalRows += $inserted;
                    $skippedRows += $chunkSkipped;

                    if ($chunkSkipped > 0) {
                        $this->logWarning(
                            'constraint_skipped',
                            sprintf('Skipped %d row(s) in table %s due to constraint violations', $chunkSkipped, $this->tableName),
                            ['table' => $this->tableName, 'skipped' => $chunkSkipped],
                        );
                    }

                    break;
                }

                $failedChunks++;

                $this->logError(
                    'chunk_failed',
                    sprintf('Chunk permanently failed after %d retries: %s', $retryCount, $e->getMessage())
                );

                throw $e;
            }
        }
    }
}
