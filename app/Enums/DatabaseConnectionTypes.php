<?php

declare(strict_types=1);

namespace App\Enums;

enum DatabaseConnectionTypes: string
{
    case MYSQL = 'mysql';
    case MARIADB = 'mariadb';
    case POSTGRESQL = 'pgsql';
    case MSSQLSERVER = 'sqlserver';
    case SQLITE = 'sqlite';

    public function getLabel(): string
    {
        return match ($this) {
            self::MYSQL => 'MySQL',
            self::MARIADB => 'MariaDB',
            self::POSTGRESQL => 'PostgreSQL',
            self::MSSQLSERVER => 'SQL Server',
            self::SQLITE => 'SQlite',
            default => $this->value,
        };
    }
}
