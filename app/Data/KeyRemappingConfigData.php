<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;

final readonly class KeyRemappingConfigData
{
    /**
     * @param  Collection<int, KeyRemappingTableData>  $tables
     */
    public function __construct(
        public bool $enabled,
        public Collection $tables,
    ) {}

    /**
     * @param  array{enabled?: bool, tables?: array<int, array{table: string, primary_key: string, strategy: string, range_min?: int, range_max?: int, foreign_keys?: array<int, array{table: string, column: string, self_referential?: bool}>}>}  $config
     */
    public static function from(array $config): self
    {
        $tables = new Collection();

        foreach ($config['tables'] ?? [] as $tableConfig) {
            $foreignKeys = new Collection();

            foreach ($tableConfig['foreign_keys'] ?? [] as $fk) {
                $foreignKeys->push(new KeyRemappingForeignKeyData(
                    table: $fk['table'],
                    column: $fk['column'],
                    selfReferential: $fk['self_referential'] ?? false,
                ));
            }

            $tables->push(new KeyRemappingTableData(
                table: $tableConfig['table'],
                primaryKey: $tableConfig['primary_key'],
                strategy: KeyRemappingStrategyEnum::from($tableConfig['strategy']),
                rangeMin: $tableConfig['range_min'] ?? 100000,
                rangeMax: $tableConfig['range_max'] ?? 9999999,
                foreignKeys: $foreignKeys,
            ));
        }

        return new self(
            enabled: $config['enabled'] ?? false,
            tables: $tables,
        );
    }

    public function getTableConfig(string $tableName): ?KeyRemappingTableData
    {
        return $this->tables->firstWhere('table', $tableName);
    }
}
