<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ColumnMutationData
{
    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public string $columnName,
        public ColumnMutationStrategyEnum $strategy,
        public array $options = [],
    ) {}
}
