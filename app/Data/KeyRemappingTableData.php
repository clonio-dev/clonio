<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;

final readonly class KeyRemappingTableData
{
    /**
     * @param  Collection<int, KeyRemappingForeignKeyData>  $foreignKeys
     */
    public function __construct(
        public string $table,
        public string $primaryKey,
        public KeyRemappingStrategyEnum $strategy,
        public int $rangeMin = 100000,
        public int $rangeMax = 9999999,
        public Collection $foreignKeys = new Collection(),
    ) {}

    /**
     * @return Collection<int, KeyRemappingForeignKeyData>
     */
    public function getSelfReferentialKeys(): Collection
    {
        return $this->foreignKeys->filter(fn (KeyRemappingForeignKeyData $fk): bool => $fk->selfReferential);
    }
}
