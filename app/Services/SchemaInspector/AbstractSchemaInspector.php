<?php

declare(strict_types=1);

namespace App\Services\SchemaInspector;

use App\Contracts\SchemaInspectorInterface;
use App\Data\ColumnSchema;
use App\Data\ConstraintSchema;
use App\Data\DatabaseSchema;
use App\Data\ForeignKeySchema;
use App\Data\IndexSchema;
use App\Data\TableMetricsData;
use App\Data\TableSchema;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;

abstract class AbstractSchemaInspector implements SchemaInspectorInterface
{
    /**
     * Abstract methods that must be implemented by DB-specific classes
     */
    abstract public function getTableSchema(Connection $connection, string $tableName): TableSchema;

    abstract public function getTableNames(Connection $connection): array;

    abstract public function getDatabaseMetadata(Connection $connection): array;

    /**
     * Abstract methods for internal use - implemented by DB-specific classes
     */
    /**
     * @return Collection<int, ColumnSchema>
     */
    abstract protected function getColumns(Connection $connection, string $tableName): Collection;

    abstract protected function getTableMetrics(Connection $connection, string $tableName): TableMetricsData;

    /**
     * @return Collection<int, IndexSchema>
     */
    abstract protected function getIndexes(Connection $connection, string $tableName): Collection;

    /**
     * @return Collection<int, ForeignKeySchema>
     */
    abstract protected function getForeignKeys(Connection $connection, string $tableName): Collection;

    /**
     * @return Collection<int, ConstraintSchema>
     */
    abstract protected function getConstraints(Connection $connection, string $tableName): Collection;

    /**
     * Get complete database schema
     */
    public function getDatabaseSchema(Connection $connection, ?string $databaseName = null): DatabaseSchema
    {
        $databaseName ??= $connection->getDatabaseName();
        $tableNames = $this->getTableNames($connection);

        $tables = collect($tableNames)->map(fn (string $tableName): TableSchema => $this->getTableSchema($connection, $tableName));

        return new DatabaseSchema(
            databaseName: $databaseName,
            databaseType: $connection->getDriverName(),
            tables: $tables,
            metadata: $this->getDatabaseMetadata($connection)
        );
    }

    /**
     * Check if table exists
     */
    public function tableExists(Connection $connection, string $tableName): bool
    {
        return in_array($tableName, $this->getTableNames($connection));
    }
}
