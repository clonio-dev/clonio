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
        public ?string $migrationTableName = null,
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
}
