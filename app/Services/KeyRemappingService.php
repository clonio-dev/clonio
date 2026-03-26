<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\KeyRemappingConfigData;
use App\Data\KeyRemappingStrategyEnum;
use App\Data\KeyRemappingTableData;
use App\Models\CloningRun;
use Illuminate\Support\Str;
use RuntimeException;

class KeyRemappingService
{
    public function __construct(
        private readonly KeyMappingRepository $mappingRepository,
    ) {}

    /**
     * Build the ID mapping for all IDs in a table and persist it to the mapping table.
     *
     * @param  array<int, mixed>  $primaryKeyValues
     * @return array<string, string> Map of old_value => new_value (as strings)
     */
    public function buildMappingForTable(
        CloningRun $run,
        KeyRemappingTableData $tableConfig,
        array $primaryKeyValues,
    ): array {
        $mappings = [];
        $usedNewValues = [];

        foreach ($primaryKeyValues as $oldValue) {
            $newValue = $this->generateNewValue($tableConfig, $oldValue, $usedNewValues);
            $mappings[(string) $oldValue] = $newValue;
            $usedNewValues[] = $newValue;
        }

        $this->mappingRepository->insertMappings(
            $run,
            $tableConfig->table,
            $tableConfig->primaryKey,
            $mappings,
        );

        return $mappings;
    }

    /**
     * Apply the ID remapping to a single row.
     *
     * Remaps the PK of the given table and all FK columns that reference remapped tables.
     * Self-referential FKs are included (disableForeignKeyConstraints must be true for this to work correctly).
     *
     * @param  array<string, mixed>  $row
     * @return array{row: array<string, mixed>, unmappedFks: int}
     */
    public function applyMapping(
        array $row,
        KeyRemappingConfigData $remappingConfig,
        string $tableName,
        CloningRun $run,
    ): array {
        $unmappedFks = 0;
        $tableConfig = $remappingConfig->getTableConfig($tableName);

        if ($tableConfig !== null) {
            $pkColumn = $tableConfig->primaryKey;
            if (array_key_exists($pkColumn, $row) && $row[$pkColumn] !== null) {
                $newValue = $this->mappingRepository->lookupNewValue(
                    $run, $tableName, $pkColumn, $row[$pkColumn]
                );

                if ($newValue !== null) {
                    $row[$pkColumn] = $tableConfig->strategy === KeyRemappingStrategyEnum::NewUuid
                        ? $newValue
                        : (int) $newValue;
                }
            }
        }

        // Remap FK columns in this row that reference other remapped tables
        foreach ($remappingConfig->tables as $sourceTableConfig) {
            foreach ($sourceTableConfig->foreignKeys as $fk) {
                if ($fk->table !== $tableName) {
                    continue;
                }

                $fkColumn = $fk->column;
                if (! array_key_exists($fkColumn, $row) || $row[$fkColumn] === null) {
                    continue;
                }

                $newValue = $this->mappingRepository->lookupNewValue(
                    $run, $sourceTableConfig->table, $sourceTableConfig->primaryKey, $row[$fkColumn]
                );

                if ($newValue !== null) {
                    $row[$fkColumn] = $sourceTableConfig->strategy === KeyRemappingStrategyEnum::NewUuid
                        ? $newValue
                        : (int) $newValue;
                } else {
                    $unmappedFks++;
                }
            }
        }

        return ['row' => $row, 'unmappedFks' => $unmappedFks];
    }

    /**
     * After all rows are inserted, update self-referential FK columns using the mapping table.
     * Only needed when FK constraints are enabled (for the two-phase insert approach).
     */
    public function applySelfReferentialMappings(
        \Illuminate\Database\Connection $targetConnection,
        string $tableName,
        KeyRemappingTableData $tableConfig,
        CloningRun $run,
    ): void {
        $selfReferentialKeys = $tableConfig->getSelfReferentialKeys();

        if ($selfReferentialKeys->isEmpty()) {
            return;
        }

        $allMappings = $this->mappingRepository->getAllMappings(
            $run,
            $tableName,
            $tableConfig->primaryKey,
        );

        foreach ($selfReferentialKeys as $fk) {
            // For each old FK value, find the new FK value and update the target rows
            // The target rows already have their remapped PK, so we match by the new PK value
            foreach ($allMappings as $oldValue => $newPkValue) {
                $newFkValue = $allMappings[$oldValue] ?? null;
                if ($newFkValue === null) {
                    continue;
                }

                $targetConnection->table($tableName)
                    ->where($tableConfig->primaryKey, (int) $newPkValue)
                    ->update([
                        $fk->column => $tableConfig->strategy === KeyRemappingStrategyEnum::NewUuid
                            ? $newFkValue
                            : (int) $newFkValue,
                    ]);
            }
        }
    }

    /**
     * @param  array<int|string, string>  $usedNewValues
     */
    private function generateNewValue(KeyRemappingTableData $config, mixed $oldValue, array $usedNewValues): string
    {
        $maxRetries = 10;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $newValue = match ($config->strategy) {
                KeyRemappingStrategyEnum::RandomInteger => (string) random_int($config->rangeMin, $config->rangeMax),
                KeyRemappingStrategyEnum::NewUuid => (string) Str::uuid(),
            };

            // New value must differ from original
            if ($newValue === (string) $oldValue) {
                continue;
            }

            // New value must not collide with already-assigned values
            if (in_array($newValue, $usedNewValues, true)) {
                continue;
            }

            return $newValue;
        }

        throw new RuntimeException(
            sprintf(
                'Failed to generate unique remapped ID for %s.%s after %d attempts.',
                $config->table,
                $config->primaryKey,
                $maxRetries,
            )
        );
    }
}
