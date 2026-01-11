<?php

declare(strict_types=1);

namespace App\Data;

final readonly class SqlServerDriverData implements ConnectionDriverData
{
    public function __construct(
        public string $database,
        public string $host,
        public string $username,
        public string $password,
        public int $port = 1433,
        public string $prefix = '',
        public bool $prefixIndexes = true,
        public string $charset = 'utf8',
        public string|null $url = null,
    ) {}

    public function toArray(): array
    {
        return [
            'driver' => 'sqlsrv',
            'url' => $this->url,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
            'charset' => $this->charset,
            'prefix' => $this->prefix,
            'prefix_indexes' => $this->prefixIndexes,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ];
    }
}
