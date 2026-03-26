<?php

declare(strict_types=1);

namespace App\Data;

final readonly class KeyRemappingForeignKeyData
{
    public function __construct(
        public string $table,
        public string $column,
        public bool $selfReferential = false,
    ) {}
}
