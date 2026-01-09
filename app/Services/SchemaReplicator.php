<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\DatabaseSchema;
use App\Data\TableSchema;
use App\Services\SchemaInspector\SchemaInspectorFactory;
use App\Services\SchemaReplicator\MySQLSchemaBuilder;
use App\Services\SchemaReplicator\PostgreSQLSchemaBuilder;
use App\Services\SchemaReplicator\SQLiteSchemaBuilder;
use App\Services\SchemaReplicator\SQLServerSchemaBuilder;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SchemaReplicator Service
 *
 * Replicates database schema from source to target database.
 * Handles schema comparison and applying structural changes.
 */
class SchemaReplicator
{
    /**
     * Replicate entire database schema
     */
    public function replicateDatabase(Connection $source, Connection $target): void
    {
        $sourceInspector = SchemaInspectorFactory::create($source);
        $targetInspector = SchemaInspectorFactory::create($target);

        $sourceSchema = $sourceInspector->getDatabaseSchema($source);
        $targetSchema = $targetInspector->getDatabaseSchema($target);

        // Replicate tables
        foreach ($sourceSchema->tables as $sourceTable) {
            $this->replicateTable($source, $target, $sourceTable);
        }

        Log::info("Schema replication completed", [
            'source_db' => $sourceSchema->databaseName,
            'target_db' => $targetSchema->databaseName,
            'tables_replicated' => $sourceSchema->getTableCount(),
        ]);
    }

    /**
     * Replicate a single table
     */
    public function replicateTable(Connection $source, Connection $target, TableSchema|string $table): void
    {
        // Get table schema if string provided
        if (is_string($table)) {
            $inspector = SchemaInspectorFactory::create($source);
            $table = $inspector->getTableSchema($source, $table);
        }

        $targetInspector = SchemaInspectorFactory::create($target);

        // Check if table exists in target
        if ($targetInspector->tableExists($target, $table->name)) {
            $this->updateTable($target, $table);
        } else {
            $this->createTable($target, $table);
        }

        Log::info("Table replicated", [
            'table' => $table->name,
            'columns' => $table->getColumnNames()->count(),
        ]);
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
            if (!$targetSchema->hasTable($sourceTable->name)) {
                $diff['missing_tables'][] = $sourceTable->name;
            } else {
                // Compare table structure
                $targetTable = $targetSchema->getTable($sourceTable->name);
                $tableDiff = $this->getTableDiff($sourceTable, $targetTable);

                if (!empty($tableDiff)) {
                    $diff['table_diffs'][$sourceTable->name] = $tableDiff;
                }
            }
        }

        // Find extra tables (in target but not in source)
        foreach ($targetSchema->tables as $targetTable) {
            if (!$sourceSchema->hasTable($targetTable->name)) {
                $diff['extra_tables'][] = $targetTable->name;
            }
        }

        return $diff;
    }

    /**
     * Get differences between two table schemas
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
            if (!$target->hasColumn($sourceColumn->name)) {
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
            if (!$source->hasColumn($targetColumn->name)) {
                $diff['extra_columns'][] = $targetColumn->name;
            }
        }

        return $diff;
    }

    /**
     * Create a new table in target database
     */
    protected function createTable(Connection $target, TableSchema $table): void
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
    }

    /**
     * Update existing table structure
     */
    protected function updateTable(Connection $target, TableSchema $table): void
    {
        $inspector = SchemaInspectorFactory::create($target);
        $currentTable = $inspector->getTableSchema($target, $table->name);

        $diff = $this->getTableDiff($table, $currentTable);

        if (empty($diff['missing_columns']) &&
            empty($diff['modified_columns'])) {
            return; // No changes needed
        }

        $driver = $target->getDriverName();
        $builder = $this->getBuilderForDriver($driver);

        // Add missing columns
        foreach ($diff['missing_columns'] as $columnName) {
            $column = $table->getColumn($columnName);
            $sql = $builder->buildAddColumn($table->name, $column);
            $target->statement($sql);
        }

        // Modify changed columns
        foreach ($diff['modified_columns'] as $columnName => $changes) {
            $column = $table->getColumn($columnName);
            $sql = $builder->buildModifyColumn($table->name, $column);
            $target->statement($sql);
        }
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
    protected function getBuilderForDriver(string $driver): object
    {
        return match ($driver) {
            'mysql' => new MySQLSchemaBuilder(),
            'pgsql' => new PostgreSQLSchemaBuilder(),
            'sqlite' => new SQLiteSchemaBuilder(),
            'sqlsrv' => new SQLServerSchemaBuilder(),
        };
    }
}
