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
        public ?TableRowSelectionData $rowSelection = null,
        public bool $enforceColumnTypes = false,
    ) {}

    /**
     * @return array<ColumnMutationData>
     */
    public function getColumnMutationsMap(): array
    {
        return $this->columnMutations->keyBy('columnName')->all();
    }
}
