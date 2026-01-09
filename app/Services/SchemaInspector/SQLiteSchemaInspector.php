<?php

declare(strict_types=1);

namespace App\Services\SchemaInspector;

use App\Data\ColumnSchema;
use App\Data\ConstraintSchema;
use App\Data\ForeignKeySchema;
use App\Data\IndexSchema;
use App\Data\TableSchema;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;

/**
 * SQLite SchemaInspector
 *
 * Inspects SQLite database schema using PRAGMA commands and sqlite_master.
 */
class SQLiteSchemaInspector extends AbstractSchemaInspector
{
    public function getTableSchema(Connection $connection, string $tableName): TableSchema
    {
        return new TableSchema(
            name: $tableName,
            columns: $this->getColumns($connection, $tableName),
            indexes: $this->getIndexes($connection, $tableName),
            foreignKeys: $this->getForeignKeys($connection, $tableName),
            constraints: $this->getConstraints($connection, $tableName),
            metadata: []
        );
    }

    public function getTableNames(Connection $connection): array
    {
        $result = $connection->select("
            SELECT name
            FROM sqlite_master
            WHERE type = 'table'
            AND name NOT LIKE 'sqlite_%'
            ORDER BY name
        ");

        return array_map(fn($row) => $row->name, $result);
    }

    public function getDatabaseMetadata(Connection $connection): array
    {
        $version = $connection->selectOne("SELECT sqlite_version() as version");

        return [
            'version' => $version->version ?? null,
            'encoding' => $connection->selectOne("PRAGMA encoding")->encoding ?? null,
        ];
    }

    protected function getColumns(Connection $connection, string $tableName): Collection
    {
        $result = $connection->select("PRAGMA table_info({$tableName})");

        return collect($result)->map(function ($column) {
            // Parse type and length (e.g., "VARCHAR(255)")
            $type = $column->type;
            $length = null;
            $scale = null;

            if (preg_match('/^([A-Z]+)\((\d+)\)$/i', $type, $matches)) {
                $type = $matches[1];
                $length = (int) $matches[2];
            }

            if (preg_match('/^([A-Z]+)\((\d+),(\d+)\)$/i', $type, $matches)) {
                $type = $matches[1];
                $length = (int) $matches[2];
                $scale = (int) $matches[3];
            }

            // Check if auto increment (SQLite uses INTEGER PRIMARY KEY AUTOINCREMENT)
            $autoIncrement = $column->pk == 1 && strtoupper($type) === 'INTEGER';

            return new ColumnSchema(
                name: $column->name,
                type: strtolower($type),
                nullable: $column->notnull == 0,
                default: $this->parseDefaultValue($column->dflt_value),
                length: $length,
                scale: $scale,
                autoIncrement: $autoIncrement,
                unsigned: false,
                charset: null,
                collation: null,
                comment: null, // SQLite doesn't support column comments
                metadata: [
                    'cid' => $column->cid,
                    'pk' => $column->pk,
                ]
            );
        });
    }

    protected function getIndexes(Connection $connection, string $tableName): Collection
    {
        // Get all indexes
        $indexes = $connection->select("PRAGMA index_list({$tableName})");

        $result = collect($indexes)->map(function ($index) use ($connection, $tableName) {
            // Get columns for this index
            $indexInfo = $connection->select("PRAGMA index_info({$index->name})");
            $columns = array_map(fn($col) => $col->name, $indexInfo);

            // Determine type
            $type = 'index';
            if ($index->origin === 'pk') {
                $type = 'primary';
            } elseif ($index->unique == 1) {
                $type = 'unique';
            }

            return new IndexSchema(
                name: $index->name,
                type: $type,
                columns: $columns,
                metadata: [
                    'origin' => $index->origin,
                ]
            );
        });

        return $result;
    }

    protected function getForeignKeys(Connection $connection, string $tableName): Collection
    {
        $result = $connection->select("PRAGMA foreign_key_list({$tableName})");

        // Group by id (foreign key constraint id)
        $grouped = collect($result)->groupBy('id');

        return $grouped->map(function ($fkColumns, $id) use ($tableName) {
            $firstColumn = $fkColumns->first();

            return new ForeignKeySchema(
                name: "fk_{$tableName}_{$id}",
                table: $tableName,
                columns: $fkColumns->pluck('from')->all(),
                referencedTable: $firstColumn->table,
                referencedColumns: $fkColumns->pluck('to')->all(),
                onUpdate: $firstColumn->on_update ?? 'NO ACTION',
                onDelete: $firstColumn->on_delete ?? 'NO ACTION'
            );
        })->values();
    }

    protected function getConstraints(Connection $connection, string $tableName): Collection
    {
        // SQLite stores constraints in the CREATE TABLE statement
        $createTable = $connection->selectOne("
            SELECT sql
            FROM sqlite_master
            WHERE type = 'table' AND name = ?
        ", [$tableName]);

        if (!$createTable || !$createTable->sql) {
            return collect();
        }

        // Parse CHECK constraints from CREATE TABLE SQL
        $constraints = collect();
        $sql = $createTable->sql;

        // Match CHECK constraints: CHECK (expression)
        if (preg_match_all('/CHECK\s*\((.*?)\)/i', $sql, $matches)) {
            foreach ($matches[1] as $index => $expression) {
                $constraints->push(new ConstraintSchema(
                    name: "check_{$tableName}_{$index}",
                    type: 'check',
                    column: null,
                    expression: trim($expression),
                    metadata: []
                ));
            }
        }

        return $constraints;
    }

    /**
     * Parse default value from SQLite format
     */
    private function parseDefaultValue(?string $default): mixed
    {
        if ($default === null || strtoupper($default) === 'NULL') {
            return null;
        }

        // Remove quotes
        if (preg_match("/^'(.*)'$/", $default, $matches)) {
            return $matches[1];
        }

        // Handle numeric
        if (is_numeric($default)) {
            return str_contains($default, '.') ? (float) $default : (int) $default;
        }

        // Return as-is for functions
        return $default;
    }
}
