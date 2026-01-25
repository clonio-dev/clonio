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

    public function getAnonymizationOptionsForTable(string $tableName): ?TableAnonymizationOptionsData
    {
        if (! $this->tableAnonymizationOptions instanceof Collection) {
            return null;
        }

        return $this->tableAnonymizationOptions
            ->firstWhere('tableName', $tableName);
    }

    /**
     * Build SynchronizationOptionsData from the stored anonymization config.
     *
     * @param  array{tables: array<int, array{tableName: string, columnMutations: array<int, array{columnName: string, strategy: string, options: array<string, mixed>}>}>}|null  $config
     */
    public static function from(?array $config): self
    {
        if (! $config || ! isset($config['tables'])) {
            return new SynchronizationOptionsData();
        }

        $tableAnonymizationOptions = new Collection();

        foreach ($config['tables'] as $tableConfig) {
            $columnMutations = new Collection();

            foreach ($tableConfig['columnMutations'] ?? [] as $mutation) {
                $strategy = ColumnMutationStrategyEnum::tryFrom($mutation['strategy']);

                if (! $strategy || $strategy === ColumnMutationStrategyEnum::KEEP) {
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

            if ($columnMutations->isNotEmpty()) {
                $tableAnonymizationOptions->push(new TableAnonymizationOptionsData(
                    tableName: $tableConfig['tableName'],
                    columnMutations: $columnMutations,
                ));
            }
        }

        return new SynchronizationOptionsData(
            tableAnonymizationOptions: $tableAnonymizationOptions->isNotEmpty() ? $tableAnonymizationOptions : null,
        );
    }
}
