<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
use App\Data\SynchronizationOptionsData;
use App\Jobs\Concerns\HandlesExceptions;
use App\Jobs\Concerns\TransferBatchJob;
use App\Models\CloningRun;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Queue\InteractsWithQueue;
use PDOException;
use Throwable;

class TransferRecordsForAllTables implements ShouldBeEncrypted, ShouldQueue
{
    use Batchable, HandlesExceptions, InteractsWithQueue, Queueable, TransferBatchJob;

    public int $tries = 2;

    public string $tableName = '';

    /**
     * @param  array<int, string>  $tables  table names
     */
    public function __construct(
        public readonly ConnectionData $sourceConnectionData,
        public readonly ConnectionData $targetConnectionData,
        public readonly SynchronizationOptionsData $options,
        public readonly array $tables,
        public readonly CloningRun $run,
    ) {}

    public function handle(
        DatabaseInformationRetrievalService $dbInformationRetrievalService,
    ): void {
        $this->logDebug('phase_started', 'Starting data transfer phase');

        try {
            foreach ($this->tables as $tableName) {
                $this->tableName = $tableName;

                $recordCount = $dbInformationRetrievalService
                    ->withConnectionForTable($this->sourceConnectionData, $tableName)
                    ->recordCount();
                $this->logInfo('table_started', "Starting table copy process for {$tableName} table ($recordCount records).");

                if ($recordCount > 0) {
                    $batch = $this->batch();
                    assert($batch !== null);
                    $batch->add(
                        new TransferRecordsForOneTable(
                            sourceConnectionData: $this->sourceConnectionData,
                            targetConnectionData: $this->targetConnectionData,
                            tableName: $tableName,
                            chunkSize: $this->options->chunkSize,
                            run: $this->run,
                            disableForeignKeyConstraints: $this->options->disableForeignKeyConstraints,
                            tableAnonymizationOptions: $this->options->getAnonymizationOptionsForTable($tableName),
                        )
                    );
                } else {
                    $this->logSuccess('table_done', "Skipped table {$tableName} because it has no records.");
                }
            }
        } catch (QueryException $e) {
            $this->handleQueryException($e);
        } catch (PDOException $e) {
            $this->handleConnectionException($e);
        } catch (Throwable $e) {
            $this->handleUnexpectedException($e);
        }
    }
}
