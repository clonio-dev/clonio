<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Str;

final readonly class ConnectionData
{
    public string $connectionName;

    public function __construct(
        public string $name,
        public ConnectionDriverData $driver,
    ) {
        $this->connectionName = 'dyn_' . Str::random() . '_' . Str::slug($name);
    }
}
