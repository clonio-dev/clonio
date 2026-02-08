<?php

declare(strict_types=1);

namespace App\Services\SchemaInspector;

use App\Contracts\SchemaInspectorInterface;
use Illuminate\Database\Connection;
use InvalidArgumentException;

/**
 * SchemaInspector Factory
 *
 * Creates the appropriate SchemaInspector instance based on database driver.
 */
class SchemaInspectorFactory
{
    /**
     * Create a SchemaInspector for the given connection
     */
    public static function create(Connection $connection): SchemaInspectorInterface
    {
        return self::createForDriver($connection->getDriverName());
    }

    /**
     * Create a SchemaInspector for a specific driver name
     */
    public static function createForDriver(string $driver): SchemaInspectorInterface
    {
        return match ($driver) {
            'mysql' => new MySQLSchemaInspector(),
            'pgsql' => new PostgreSQLSchemaInspector(),
            'sqlite' => new SQLiteSchemaInspector(),
            'sqlsrv' => new SQLServerSchemaInspector(),
            default => throw new InvalidArgumentException(
                "Unsupported database driver: {$driver}. " .
                'Supported drivers are: mysql, pgsql, sqlite, sqlsrv'
            ),
        };
    }
}
