<?php

declare(strict_types=1);

namespace App\Data;

final readonly class SqliteDriverData implements ConnectionDriverData
{
    public function __construct(
        public string $database = ':memory:',
    ) {}

    public function toArray(): array
    {
        return [
            'driver' => 'sqlite',
            'database' => $this->database,
        ];
    }
}
