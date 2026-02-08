<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
use App\Data\RowSelectionStrategyEnum;
use App\Data\SynchronizationOptionsData;
use App\Data\TableRowSelectionData;
use App\Jobs\Concerns\HandlesExceptions;
use App\Jobs\Concerns\LogsProcessSteps;
use App\Jobs\Concerns\TransferBatchJob;
use App\Models\CloningRun;
use App\Services\DatabaseInformationRetrievalService;
use App\Services\SchemaInspector\SchemaInspectorFactory;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Queue\InteractsWithQueue;
use PDOException;
use Throwable;

class TransferRecordsForAllTables implements ShouldBeEncrypted, ShouldQueue
{
    use Batchable, HandlesExceptions, InteractsWithQueue, LogsProcessSteps, Queueable, TransferBatchJob;

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
        try {
            foreach ($this->tables as $tableName) {
                $this->tableName = $tableName;

                $recordCount = $dbInformationRetrievalService
                    ->withConnectionForTable($this->sourceConnectionData, $tableName)
                    ->recordCount();

                if ($recordCount <= 0) {
                    $this->logSuccess('table_done', "Skipped table {$tableName} because it has no records.");

                    continue;
                }

                $foreignKeyFilters = $this->buildForeignKeyFilters(
                    $dbInformationRetrievalService,
                    $tableName
                );

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
                        foreignKeyFilters: $foreignKeyFilters,
                    )
                );
            }
        } catch (QueryException $e) {
            $this->handleQueryException($e);
        } catch (PDOException $e) {
            $this->handleConnectionException($e);
        } catch (Throwable $e) {
            $this->handleUnexpectedException($e);
        }
    }

    /**
     * Build FK filters for a child table whose parent has row selection applied.
     *
     * When a parent table is row-limited, child tables should only transfer rows
     * that reference the transferred parent rows (already present on target).
     *
     * @return array<int, array{column: string, values: array<int, mixed>}>|null
     */
    private function buildForeignKeyFilters(
        DatabaseInformationRetrievalService $dbInformationRetrievalService,
        string $tableName,
    ): ?array {
        $sourceConnection = $dbInformationRetrievalService->getConnection($this->sourceConnectionData);
        assert($sourceConnection instanceof Connection);

        $inspector = SchemaInspectorFactory::create($sourceConnection);

        $tableSchema = $inspector->getTableSchema($sourceConnection, $tableName);
        $filters = [];

        foreach ($tableSchema->getForeignKeys() as $fk) {
            if (! in_array($fk->referencedTable, $this->tables)) {
                continue;
            }

            $parentOptions = $this->options->getAnonymizationOptionsForTable($fk->referencedTable);
            if (! $parentOptions?->rowSelection instanceof TableRowSelectionData) {
                continue;
            }
            if ($parentOptions->rowSelection->strategy === RowSelectionStrategyEnum::FullTable) {
                continue;
            }

            // Parent was row-limited: filter child to only include matching FK values from target
            $parentIds = $sourceConnection
                ->table($fk->referencedTable)
                ->orderBy($fk->referencedColumns[0], $parentOptions->rowSelection->strategy === RowSelectionStrategyEnum::FirstX ? 'asc' : 'desc')
                ->limit($parentOptions->rowSelection->limit)
                ->pluck($fk->referencedColumns[0])
                ->all();

            $filters[] = [
                'column' => $fk->columns[0],
                'values' => $parentIds,
            ];
        }

        return $filters !== [] ? $filters : null;
    }
}
