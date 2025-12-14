<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ConnectionData
{
    public function __construct(
        public string $name,
        public ConnectionDriverData $driver,
    ) {}

    public function connectionName(): string
    {
        return uniqid('dyn_', true) . $this->name;
    }
}
