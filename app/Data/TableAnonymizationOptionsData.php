<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;

final readonly class TableAnonymizationOptionsData
{
    /**
     * @param  Collection<int, ColumnMutationData>  $columnMutations
     */
    public function __construct(
        public string $tableName,
        public Collection $columnMutations,
    ) {}

    /**
     * @return array<string, ColumnMutationData>
     */
    public function getColumnMutationsMap(): array
    {
        return $this->columnMutations->keyBy('columnName')->all();
    }
}
