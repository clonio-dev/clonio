<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;

final readonly class SynchronizationOptionsData
{
    /**
     * @param  Collection<int, TableAnonymizationOptionsData>  $tableAnonymizationOptions
     */
    public function __construct(
        public bool $disableForeignKeyConstraints = true,
        public bool $keepUnknownTablesOnTarget = true,
        public int $chunkSize = 1000,
        public ?Collection $tableAnonymizationOptions = null,
    ) {}

    /**
     * Build SynchronizationOptionsData from the stored anonymization config.
     *
     * @param  array{keepUnknownTablesOnTarget?: bool, tables: array<int, array{tableName: string, columnMutations?: array<int, array{columnName: string, strategy: string, options: array<string, mixed>}>, rowSelection?: array{strategy: string, limit?: int, sortColumn?: string|null}}>}|null  $config
     */
    public static function from(?array $config): self
    {
        if (! $config) {
            return new self();
        }

        if (! isset($config['tables'])) {
            return new self(
                keepUnknownTablesOnTarget: $config['keepUnknownTablesOnTarget'] ?? true,
            );
        }

        $tableAnonymizationOptions = new Collection();

        foreach ($config['tables'] as $tableConfig) {
            $columnMutations = new Collection();

            foreach ($tableConfig['columnMutations'] ?? [] as $mutation) {
                $strategy = ColumnMutationStrategyEnum::tryFrom($mutation['strategy']);
                if (! $strategy) {
                    continue;
                }

                if ($strategy === ColumnMutationStrategyEnum::KEEP) {
                    continue;
                }

                $options = $mutation['options'] ?? [];

                $columnMutations->push(new ColumnMutationData(
                    columnName: $mutation['columnName'],
                    strategy: $strategy,
                    options: new ColumnMutationDataOptions(
                        fakerMethod: $options['fakerMethod'] ?? 'word',
                        fakerMethodArguments: $options['fakerMethodArguments'] ?? [],
                        visibleChars: $options['visibleChars'] ?? 2,
                        maskChar: $options['maskChar'] ?? '*',
                        preserveFormat: $options['preserveFormat'] ?? false,
                        algorithm: $options['algorithm'] ?? 'sha256',
                        salt: $options['salt'] ?? '',
                        value: $options['value'] ?? null,
                    ),
                ));
            }

            $rowSelection = null;
            if (isset($tableConfig['rowSelection'])) {
                $rs = $tableConfig['rowSelection'];
                $rowSelection = new TableRowSelectionData(
                    strategy: RowSelectionStrategyEnum::from($rs['strategy']),
                    limit: $rs['limit'] ?? 1000,
                    sortColumn: $rs['sortColumn'] ?? null,
                );
            }

            $enforceColumnTypes = $tableConfig['enforceColumnTypes'] ?? false;

            if ($columnMutations->isNotEmpty() || $rowSelection instanceof TableRowSelectionData || $enforceColumnTypes) {
                $tableAnonymizationOptions->push(new TableAnonymizationOptionsData(
                    tableName: $tableConfig['tableName'],
                    columnMutations: $columnMutations,
                    rowSelection: $rowSelection,
                    enforceColumnTypes: $enforceColumnTypes,
                ));
            }
        }

        return new self(
            keepUnknownTablesOnTarget: $config['keepUnknownTablesOnTarget'] ?? true,
            tableAnonymizationOptions: $tableAnonymizationOptions->isNotEmpty() ? $tableAnonymizationOptions : null,
        );
    }

    public function getAnonymizationOptionsForTable(string $tableName): ?TableAnonymizationOptionsData
    {
        if (! $this->tableAnonymizationOptions instanceof Collection) {
            return null;
        }

        return $this->tableAnonymizationOptions
            ->firstWhere('tableName', $tableName);
    }
}
