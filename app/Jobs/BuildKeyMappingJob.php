<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
use App\Data\KeyRemappingConfigData;
use App\Data\RowSelectionStrategyEnum;
use App\Data\SynchronizationOptionsData;
use App\Data\TableRowSelectionData;
use App\Jobs\Concerns\HandlesExceptions;
use App\Jobs\Concerns\LogsProcessSteps;
use App\Jobs\Concerns\TransferBatchJob;
use App\Models\CloningRun;
use App\Services\DatabaseInformationRetrievalService;
use App\Services\KeyMappingRepository;
use App\Services\KeyRemappingService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class BuildKeyMappingJob implements ShouldBeEncrypted, ShouldQueue
{
    use Batchable;
    use HandlesExceptions;
    use InteractsWithQueue;
    use LogsProcessSteps;
    use Queueable;
    use TransferBatchJob;

    public int $tries = 1;

    public function __construct(
        public readonly ConnectionData $sourceConnectionData,
        public readonly KeyRemappingConfigData $keyRemappingConfig,
        public readonly SynchronizationOptionsData $options,
        public readonly CloningRun $run,
    ) {}

    public function handle(
        DatabaseInformationRetrievalService $dbInformationRetrievalService,
        KeyMappingRepository $mappingRepository,
        KeyRemappingService $remappingService,
    ): void {
        $this->logInfo('key_remapping_started', 'Building ID mapping table', [
            'tables' => $this->keyRemappingConfig->tables->pluck('table')->all(),
            'strategies' => $this->keyRemappingConfig->tables
                ->mapWithKeys(fn ($t) => [$t->table => $t->strategy->value])
                ->all(),
        ]);

        try {
            $sourceConnection = $dbInformationRetrievalService->getConnection($this->sourceConnectionData);
            assert($sourceConnection instanceof Connection);
        } catch (Throwable $e) {
            $this->handleConnectionException($e);

            return;
        }

        $mappingRepository->createTable($this->run);

        foreach ($this->keyRemappingConfig->tables as $tableConfig) {
            $tableName = $tableConfig->table;
            $pkColumn = $tableConfig->primaryKey;
            $startTime = microtime(true);

            $query = $sourceConnection->table($tableName);

            // Apply the same row selection as the transfer job would use
            $tableOptions = $this->options->getAnonymizationOptionsForTable($tableName);
            $rowSelection = $tableOptions?->rowSelection;
            $hasRowLimit = $rowSelection instanceof TableRowSelectionData
                && $rowSelection->strategy !== RowSelectionStrategyEnum::FullTable;
            $transferMode = 'full';

            if ($hasRowLimit) {
                $direction = $rowSelection->strategy === RowSelectionStrategyEnum::FirstX ? 'asc' : 'desc';
                $sortColumn = $rowSelection->sortColumn ?? $pkColumn;
                $query->orderBy($sortColumn, $direction)->limit($rowSelection->limit);
                $transferMode = $rowSelection->strategy === RowSelectionStrategyEnum::FirstX ? 'first_x' : 'last_x';
            }

            // Fetch PKs in chunks of 10,000 to handle large tables
            $idsMapped = 0;
            $offset = 0;
            $chunkSize = 10000;

            do {
                if ($hasRowLimit) {
                    // When row-limited, fetch all at once and chunk in memory
                    $chunk = $query->pluck($pkColumn)->all();
                    $pageValues = array_slice($chunk, $offset, $chunkSize);
                } else {
                    $pageValues = $sourceConnection->table($tableName)
                        ->orderBy($pkColumn)
                        ->offset($offset)
                        ->limit($chunkSize)
                        ->pluck($pkColumn)
                        ->all();
                }

                if ($pageValues === []) {
                    break;
                }

                $remappingService->buildMappingForTable($this->run, $tableConfig, $pageValues);
                $idsMapped += count($pageValues);
                $offset += $chunkSize;
            } while (! $hasRowLimit && count($pageValues) === $chunkSize);

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            $logData = [
                'table' => $tableName,
                'transfer_mode' => $transferMode,
                'ids_mapped' => $idsMapped,
                'duration_ms' => $durationMs,
            ];

            if ($hasRowLimit) {
                $logData['limit'] = $rowSelection->limit;
            }

            $this->logInfo('mapping_built', sprintf('Mapped %d IDs for table %s', $idsMapped, $tableName), $logData);
        }
    }
}
