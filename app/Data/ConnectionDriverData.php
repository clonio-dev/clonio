<?php

declare(strict_types=1);

namespace App\Data;

interface ConnectionDriverData {
    /**
     * @return array{driver: string, database: string, ...<string, mixed>}
     */
    public function toArray(): array;
}
