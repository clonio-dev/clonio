<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SchemaBuilderInterface;
use App\Data\TableSchema;
use App\Services\SchemaInspector\SchemaInspectorFactory;
use App\Services\SchemaReplicator\MySQLSchemaBuilder;
use App\Services\SchemaReplicator\PostgreSQLSchemaBuilder;
use App\Services\SchemaReplicator\SQLiteSchemaBuilder;
use App\Services\SchemaReplicator\SQLServerSchemaBuilder;
use Illuminate\Database\Connection;
use Throwable;

/**
 * SchemaReplicator Service
 *
 * Replicates database schema from source to target database.
 * Handles schema comparison and applying structural changes.
 */
class SchemaReplicator
{
    public function __construct(public readonly DependencyResolver $dependencyResolver) {}

    /**
     * Replicate entire database schema
     *
     * @param  array<string, bool>  $enforceColumnTypesMap  Table name => whether to enforce column types
     */
    public function replicateDatabase(Connection $source, Connection $target, array $tables = [], ?callable $visitor = null, array $enforceColumnTypesMap = []): void
    {
        if ($tables === []) {
            $tables = $this->resolveTables($source);
        }

        // Replicate tables
        foreach ($tables as $sourceTable) {
            $tableName = is_string($sourceTable) ? $sourceTable : $sourceTable->name;
            $enforceColumnTypes = $enforceColumnTypesMap[$tableName] ?? false;
            $this->replicateTable($source, $target, $sourceTable, $visitor, $enforceColumnTypes);
        }
    }

    /**
     * Replicate a single table
     */
    public function replicateTable(Connection $source, Connection $target, TableSchema|string $table, ?callable $visitor = null, bool $enforceColumnTypes = false): void
    {
        // Get table schema if string provided
        if (is_string($table)) {
            $inspector = SchemaInspectorFactory::create($source);
            $table = $inspector->getTableSchema($source, $table);
        }

        $targetInspector = SchemaInspectorFactory::create($target);

        // Check if table exists in target
        if ($targetInspector->tableExists($target, $table->name)) {
            if ($visitor !== null) {
                $visitor($table->name, 'replicating_table', 'Updating existing table');
            }
            $this->updateTable($target, $table, $visitor, $enforceColumnTypes);
        } else {
            if ($visitor !== null) {
                $visitor($table->name, 'replicating_table', 'Creating new table');
            }
            $this->createTable($target, $table, $visitor);
        }
    }

    /**
     * Get schema differences between source and target
     */
    public function getSchemaDiff(Connection $source, Connection $target): array
    {
        $sourceInspector = SchemaInspectorFactory::create($source);
        $targetInspector = SchemaInspectorFactory::create($target);

        $sourceSchema = $sourceInspector->getDatabaseSchema($source);
        $targetSchema = $targetInspector->getDatabaseSchema($target);

        $diff = [
            'missing_tables' => [],
            'extra_tables' => [],
            'table_diffs' => [],
        ];

        // Find missing tables (in source but not in target)
        foreach ($sourceSchema->tables as $sourceTable) {
            if (! $targetSchema->hasTable($sourceTable->name)) {
                $diff['missing_tables'][] = $sourceTable->name;
            } else {
                // Compare table structure
                $targetTable = $targetSchema->getTable($sourceTable->name);
                $tableDiff = $this->getTableDiff($sourceTable, $targetTable);

                if ($tableDiff !== []) {
                    $diff['table_diffs'][$sourceTable->name] = $tableDiff;
                }
            }
        }

        // Find extra tables (in target but not in source)
        foreach ($targetSchema->tables as $targetTable) {
            if (! $sourceSchema->hasTable($targetTable->name)) {
                $diff['extra_tables'][] = $targetTable->name;
            }
        }

        return $diff;
    }

    /**
     * Get differences between two table schemas
     *
     * @return array{
     *     missing_columns: string[],
     *     extra_columns: string[],
     *     modified_columns: string[],
     * }
     */
    public function getTableDiff(TableSchema $source, TableSchema $target): array
    {
        $diff = [
            'missing_columns' => [],
            'extra_columns' => [],
            'modified_columns' => [],
        ];

        // Find missing columns
        foreach ($source->columns as $sourceColumn) {
            if (! $target->hasColumn($sourceColumn->name)) {
                $diff['missing_columns'][] = $sourceColumn->name;
            } else {
                // Compare column properties
                $targetColumn = $target->getColumn($sourceColumn->name);

                if ($this->columnsAreDifferent($sourceColumn, $targetColumn)) {
                    $diff['modified_columns'][$sourceColumn->name] = [
                        'source' => $sourceColumn->toArray(),
                        'target' => $targetColumn->toArray(),
                    ];
                }
            }
        }

        // Find extra columns
        foreach ($target->columns as $targetColumn) {
            if (! $source->hasColumn($targetColumn->name)) {
                $diff['extra_columns'][] = $targetColumn->name;
            }
        }

        return $diff;
    }

    /**
     * Create a new table in target database
     */
    protected function createTable(Connection $target, TableSchema $table, ?callable $visitor = null): void
    {
        $driver = $target->getDriverName();
        $builder = $this->getBuilderForDriver($driver);

        $sql = $builder->buildCreateTable($table);

        $target->statement($sql);

        // Create indexes
        foreach ($table->indexes as $index) {
            if ($index->type !== 'primary') { // Primary key already created with table
                $sql = $builder->buildCreateIndex($table->name, $index);
                $target->statement($sql);
            }
        }

        // Create foreign keys
        foreach ($table->foreignKeys as $fk) {
            try {
                $sql = $builder->buildAddForeignKey($table->name, $fk);
            } catch (Throwable) {
                continue;
            }

            $target->statement($sql);
        }

        if ($visitor !== null) {
            $visitor($table->name, 'replicating_table', 'Missing table created');
        }
    }

    /**
     * Update existing table structure
     *
     * By default, only checks column existence and adds missing columns.
     * When $enforceColumnTypes is true, also checks and modifies column types.
     */
    protected function updateTable(Connection $target, TableSchema $table, ?callable $visitor = null, bool $enforceColumnTypes = false): void
    {
        $inspector = SchemaInspectorFactory::create($target);
        $currentTable = $inspector->getTableSchema($target, $table->name);

        $diff = $this->getTableDiff($table, $currentTable);

        $hasMissingColumns = ! empty($diff['missing_columns']);
        $hasModifiedColumns = $enforceColumnTypes && ! empty($diff['modified_columns']);

        if (! $hasMissingColumns && ! $hasModifiedColumns) {
            if ($visitor !== null) {
                $visitor($table->name, 'replicating_table', 'No changes necessary', 'success');
            }

            return; // No changes needed
        }

        $driver = $target->getDriverName();
        $builder = $this->getBuilderForDriver($driver);

        // Add missing columns
        foreach ($diff['missing_columns'] as $columnName) {
            $column = $table->getColumn($columnName);
            $sql = $builder->buildAddColumn($table->name, $column);
            $target->statement($sql);

            if ($visitor !== null) {
                $visitor($table->name, 'replicating_table', 'Missing column added: ' . $columnName, 'success');
            }
        }

        // Modify changed columns (only when enforceColumnTypes is enabled)
        if ($enforceColumnTypes) {
            foreach (array_keys($diff['modified_columns']) as $columnName) {
                $column = $table->getColumn($columnName);
                $sql = $builder->buildModifyColumn($table->name, $column);
                $target->statement($sql);

                if ($visitor !== null) {
                    $visitor($table->name, 'replicating_table', 'Modified an existing column: ' . $columnName, 'success');
                }
            }
        }

        // @TODO remove columns, that aren't in source
    }

    /**
     * Check if two columns have different properties
     */
    protected function columnsAreDifferent($source, $target): bool
    {
        return $source->type !== $target->type ||
            $source->nullable !== $target->nullable ||
            $source->length !== $target->length ||
            $source->scale !== $target->scale ||
            $source->default !== $target->default;
    }

    /**
     * Get SQL builder for specific driver
     */
    protected function getBuilderForDriver(string $driver): SchemaBuilderInterface
    {
        return match ($driver) {
            'mysql' => new MySQLSchemaBuilder(),
            'pgsql' => new PostgreSQLSchemaBuilder(),
            'sqlite' => new SQLiteSchemaBuilder(),
            'sqlsrv' => new SQLServerSchemaBuilder(),
        };
    }

    /**
     * @return array<int, string>
     */
    private function resolveTables(Connection $source): array
    {
        $sourceInspector = SchemaInspectorFactory::create($source);
        $sourceSchema = $sourceInspector->getDatabaseSchema($source);

        $tableNames = $sourceSchema->getTableNames()->all();

        $order = $this->dependencyResolver->getProcessingOrder($tableNames, $source);

        return $order['insert_order'];
    }
}
