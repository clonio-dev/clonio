<?php

declare(strict_types=1);

namespace App\Data;

final readonly class TableRowSelectionData
{
    public function __construct(
        public RowSelectionStrategyEnum $strategy = RowSelectionStrategyEnum::FullTable,
        public int $limit = 1000,
        public ?string $sortColumn = null,
    ) {}
}
