<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Str;

final readonly class ConnectionData
{
    public function __construct(
        public string $name,
        public ConnectionDriverData $driver,
    ) {}

    public function connectionName(): string
    {
        return 'dyn_' . Str::random() . '_' . $this->name;
    }
}
