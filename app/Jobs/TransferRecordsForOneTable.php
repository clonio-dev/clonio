<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
use App\Data\TableAnonymizationOptionsData;
use App\Jobs\Concerns\ClassifiesError;
use App\Jobs\Concerns\HandlesExceptions;
use App\Jobs\Concerns\TransferBatchJob;
use App\Models\TransferRun;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use PDOException;
use RuntimeException;
use stdClass;
use Throwable;

class TransferRecordsForOneTable implements ShouldBeEncrypted, ShouldQueue
{
    use Batchable, ClassifiesError, HandlesExceptions, InteractsWithQueue, Queueable, TransferBatchJob;

    public int $tries = 1;

    public function __construct(
        public readonly ConnectionData $sourceConnectionData,
        public readonly ConnectionData $targetConnectionData,
        public readonly string $tableName,
        public readonly int $chunkSize,
        public readonly TransferRun $run,
        public readonly bool $disableForeignKeyConstraints = false,
        public readonly ?TableAnonymizationOptionsData $tableAnonymizationOptions = null,
    ) {}

    public function handle(
        DatabaseInformationRetrievalService $dbInformationRetrievalService,
        AnonymizationService $anonymizationService,
    ): void {
        Log::info(__CLASS__ . ':' . $this->tableName);
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
            Log::debug('Disabling foreign key constraints on target database.');
            $targetConnection->getSchemaBuilder()->disableForeignKeyConstraints();
        }

        $query = $sourceTable->query();

        $orderColumns = $sourceTable->orderColumns();
        Log::debug("Order columns for table {$this->tableName}: " . implode(', ', $orderColumns));
        foreach ($orderColumns as $column) {
            $query->orderBy($column);
        }

        $totalRows = 0;
        $failedChunks = 0;
        $maxChunkRetries = 3;

        try {
            $this->logInfo(
                'data_copy_started',
                "Starting chunked data copy (chunk size: {$this->chunkSize})"
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
                    $anonymizationService
                ): void {
                    $this->logDebug('chunk_processing', "Transferring {$records->count()} records from {$this->tableName} table.");

                    $retryCount = 0;

                    while ($retryCount < $maxChunkRetries) {
                        try {
                            // Rows zu Array konvertieren
                            $rowsArray = $records->map(function (object $record, int $index) use ($anonymizationService
                            ): array {
                                $record = get_object_vars($record);

                                return $anonymizationService->anonymizeRecord($record,
                                    $this->tableAnonymizationOptions);
                            })->values()->all();

                            $targetConnection
                                ->table($this->tableName)
                                ->insert($rowsArray);

                            $totalRows += $records->count();

                            $this->logDebug(
                                'chunk_processed',
                                "Processed chunk: {$totalRows} rows total"
                            );

                            break; // Erfolg, raus aus Retry-Loop

                        } catch (QueryException $e) {
                            $retryCount++;

                            if ($this->isTemporaryError($e) && $retryCount < $maxChunkRetries) {
                                // Temporärer Fehler - Retry
                                $this->logWarning(
                                    'chunk_retry',
                                    "Chunk failed (attempt {$retryCount}/{$maxChunkRetries}), retrying: {$e->getMessage()}"
                                );

                                Sleep::sleep(2 * $retryCount); // Exponential Backoff

                                continue;
                            }

                            // Permanenter Fehler oder Max Retries erreicht
                            $failedChunks++;

                            $this->logError(
                                'chunk_failed',
                                "Chunk permanently failed after {$retryCount} retries: {$e->getMessage()}"
                            );

                            throw $e;
                        }
                    }
                }
            );

            $this->logInfo(
                'data_copy_completed',
                "Data copy completed. Total rows: {$totalRows}, Failed chunks: {$failedChunks}"
            );
            $this->logInfo('table_done', "Table {$this->tableName} transferring records done.");

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
            $this->logInfo('table_done', "Table {$this->tableName} transferring records done with errors.");

            if ($this->disableForeignKeyConstraints) {
                Log::debug('Enabling foreign key constraints on target database.');
                $targetConnection->getSchemaBuilder()->enableForeignKeyConstraints();
            }
        }
    }
}
