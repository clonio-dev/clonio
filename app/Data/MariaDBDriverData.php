<?php

declare(strict_types=1);

namespace App\Data;

use PDO;

final readonly class MariaDBDriverData implements ConnectionDriverData
{
    public function __construct(
        public string $database,
        public string $host,
        public string $username,
        public string $password,
        public int $port = 3306,

        public ?string $engine = null,
        public string $prefix = '',
        public bool $prefixIndexes = true,
        public bool $strict = true,
        public string $charset = 'utf8mb4',
        public string $collation = 'utf8mb4_unicode_ci',
        public string $socket = '',
        public ?string $ssl = null,
        public ?string $url = null,
    ) {}

    public function toArray(): array
    {
        return [
            'driver' => 'mariadb',
            'url' => $this->url,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
            'unix_socket' => $this->socket,
            'charset' => $this->charset,
            'collation' => $this->collation,
            'prefix' => $this->prefix,
            'prefix_indexes' => $this->prefixIndexes,
            'strict' => $this->strict,
            'engine' => $this->engine,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? \Pdo\Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => $this->ssl,
            ]) : [],
        ];
    }
}
