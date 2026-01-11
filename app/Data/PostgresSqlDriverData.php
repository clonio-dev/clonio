<?php

declare(strict_types=1);

namespace App\Data;

final readonly class PostgresSqlDriverData implements ConnectionDriverData
{
    public function __construct(
        public string $database,
        public string $host,
        public string $username,
        public string $password,
        public int $port = 5432,
        public string $prefix = '',
        public bool $prefixIndexes = true,
        public string $charset = 'utf8',
        public string $schema = 'public',
        public string $ssl = 'prefer',
        public string|null $url = null,
    ) {}

    public function toArray(): array
    {
        return [
            'driver' => 'pgsql',
            'url' => $this->url,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
            'charset' => $this->charset,
            'prefix' => $this->prefix,
            'prefix_indexes' => $this->prefixIndexes,
            'search_path' => $this->schema,
            'sslmode' => $this->ssl,
        ];
    }
}
