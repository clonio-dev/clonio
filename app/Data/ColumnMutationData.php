<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ColumnMutationData
{
    public function __construct(
        public string $columnName,
        public ColumnMutationStrategyEnum $strategy,
        public ColumnMutationDataOptions $options = new ColumnMutationDataOptions(),
    ) {}
}
