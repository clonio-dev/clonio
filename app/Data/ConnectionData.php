<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Str;

final readonly class ConnectionData
{
    private string $internalConnectionName;

    public function __construct(
        public string $name,
        public ConnectionDriverData $driver,
    ) {
        $this->internalConnectionName = 'dyn_' . Str::random() . '_' . $name;
    }

    public function connectionName(): string
    {
        return $this->internalConnectionName;
    }
}
