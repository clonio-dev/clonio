<?php

declare(strict_types=1);

namespace App\Services\SchemaInspector;

use App\Data\ColumnSchema;
use App\Data\ConstraintSchema;
use App\Data\ForeignKeySchema;
use App\Data\IndexSchema;
use App\Data\TableMetricsData;
use App\Data\TableSchema;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;

class MySQLSchemaInspector extends AbstractSchemaInspector
{
    public function getTableSchema(Connection $connection, string $tableName): TableSchema
    {
        return new TableSchema(
            name: $tableName,
            columns: $this->getColumns($connection, $tableName),
            indexes: $this->getIndexes($connection, $tableName),
            foreignKeys: $this->getForeignKeys($connection, $tableName),
            constraints: $this->getConstraints($connection, $tableName),
            metadata: $this->getTableMetadata($connection, $tableName),
            metricsData: $this->getTableMetrics($connection, $tableName),
        );
    }

    /**
     * @return string[]
     */
    public function getTableNames(Connection $connection): array
    {
        $result = $connection->select("
            SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = ?
            AND TABLE_TYPE = 'BASE TABLE'
            ORDER BY TABLE_NAME
        ", [$connection->getDatabaseName()]);

        // @phpstan-ignore return.type
        return array_map(
            // @phpstan-ignore property.nonObject
            fn ($row) => $row->TABLE_NAME,
            $result,
        );
    }

    public function getDatabaseMetadata(Connection $connection): array
    {
        $result = $connection->selectOne('
            SELECT
                VERSION() as version,
                DEFAULT_CHARACTER_SET_NAME as charset,
                DEFAULT_COLLATION_NAME as collation
            FROM INFORMATION_SCHEMA.SCHEMATA
            WHERE SCHEMA_NAME = ?
        ', [$connection->getDatabaseName()]);

        // @phpstan-ignore return.type
        return [
            // @phpstan-ignore-next-line
            'version' => $result->version ?? null,
            // @phpstan-ignore-next-line
            'charset' => $result->charset ?? null,
            // @phpstan-ignore-next-line
            'collation' => $result->collation ?? null,
        ];
    }

    protected function getColumns(Connection $connection, string $tableName): Collection
    {
        $result = $connection->select('
            SELECT
                COLUMN_NAME as name,
                COLUMN_TYPE as column_type,
                DATA_TYPE as data_type,
                IS_NULLABLE as is_nullable,
                COLUMN_DEFAULT as default_value,
                CHARACTER_MAXIMUM_LENGTH as max_length,
                NUMERIC_PRECISION as numeric_precision,
                NUMERIC_SCALE as numeric_scale,
                EXTRA as extra,
                CHARACTER_SET_NAME as charset,
                COLLATION_NAME as collation,
                COLUMN_COMMENT as comment
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ', [$connection->getDatabaseName(), $tableName]);

        return collect($result)->map(function ($column): ColumnSchema {
            // Parse length from column_type (e.g., "varchar(255)" -> 255)
            $length = null;
            // @phpstan-ignore-next-line
            if (preg_match('/\((\d+)\)/', (string) $column->column_type, $matches)) {
                $length = (int) $matches[1];
            }

            // Check if unsigned
            // @phpstan-ignore-next-line
            $unsigned = str_contains((string) $column->column_type, 'unsigned');

            // Check if auto increment
            // @phpstan-ignore-next-line
            $autoIncrement = str_contains((string) $column->extra, 'auto_increment');

            return new ColumnSchema(
                // @phpstan-ignore-next-line
                name: $column->name,
                // @phpstan-ignore-next-line
                type: $column->data_type,
                // @phpstan-ignore-next-line
                nullable: $column->is_nullable === 'YES',
                // @phpstan-ignore-next-line
                default: $this->parseDefaultValue($column->default_value),
                // @phpstan-ignore-next-line
                length: $length ?? ($column->max_length ? (int) $column->max_length : null),
                // @phpstan-ignore-next-line
                scale: $column->numeric_scale ? (int) $column->numeric_scale : null,
                autoIncrement: $autoIncrement,
                unsigned: $unsigned,
                // @phpstan-ignore-next-line
                charset: $column->charset,
                // @phpstan-ignore-next-line
                collation: $column->collation,
                // @phpstan-ignore-next-line
                comment: $column->comment ?: null,
                metadata: [
                    // @phpstan-ignore-next-line
                    'column_type' => $column->column_type,
                    // @phpstan-ignore-next-line
                    'extra' => $column->extra,
                ]
            );
        });
    }

    protected function getTableMetrics(Connection $connection, string $tableName): TableMetricsData
    {
        $result = $connection->selectOne('
            SELECT
                TABLE_ROWS as row_count,
                DATA_LENGTH as data_size
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
        ', [$connection->getDatabaseName(), $tableName]);

        if (! $result) {
            return new TableMetricsData(rowsCount: 0, dataSizeInBytes: 0);
        }

        $rowCount = $result->row_count ?? 0;
        $dataSize = $result->data_size ?? 0;

        return new TableMetricsData(
            rowsCount: (int) $rowCount,
            dataSizeInBytes: $dataSize
        );
    }

    protected function getIndexes(Connection $connection, string $tableName): Collection
    {
        $result = $connection->select('
            SELECT
                INDEX_NAME as name,
                NON_UNIQUE as non_unique,
                COLUMN_NAME as column_name,
                SEQ_IN_INDEX as sequence,
                INDEX_TYPE as index_type
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
            ORDER BY INDEX_NAME, SEQ_IN_INDEX
        ', [$connection->getDatabaseName(), $tableName]);

        // Group by index name and collect columns
        $grouped = collect($result)->groupBy('name');

        return $grouped->map(function ($indexColumns, $indexName): IndexSchema {
            $firstColumn = $indexColumns->first();

            // Determine index type
            $type = 'index';
            if ($indexName === 'PRIMARY') {
                $type = 'primary';
                // @phpstan-ignore-next-line
            } elseif ($firstColumn->non_unique === 0) {
                $type = 'unique';
                // @phpstan-ignore-next-line
            } elseif ($firstColumn->index_type === 'FULLTEXT') {
                $type = 'fulltext';
                // @phpstan-ignore-next-line
            } elseif ($firstColumn->index_type === 'SPATIAL') {
                $type = 'spatial';
            }

            return new IndexSchema(
                name: $indexName,
                type: $type,
                // @phpstan-ignore-next-line
                columns: $indexColumns->pluck('column_name')->all(),
                metadata: [
                    // @phpstan-ignore-next-line
                    'index_type' => $firstColumn->index_type,
                ]
            );
        })->values();
    }

    protected function getForeignKeys(Connection $connection, string $tableName): Collection
    {
        $result = $connection->select('
            SELECT
                kcu.CONSTRAINT_NAME as name,
                kcu.TABLE_NAME as table_name,
                kcu.COLUMN_NAME as column_name,
                kcu.REFERENCED_TABLE_NAME as referenced_table,
                kcu.REFERENCED_COLUMN_NAME as referenced_column,
                rc.UPDATE_RULE as on_update,
                rc.DELETE_RULE as on_delete
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
            JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
                ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
            WHERE kcu.TABLE_SCHEMA = ?
            AND kcu.TABLE_NAME = ?
            AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION
        ', [$connection->getDatabaseName(), $tableName]);

        // Group by constraint name for composite foreign keys
        $grouped = collect($result)->groupBy('name');

        return $grouped->map(function ($fkColumns, $constraintName) use ($tableName): ForeignKeySchema {
            $firstColumn = $fkColumns->first();

            return new ForeignKeySchema(
                name: $constraintName,
                table: $tableName,
                // @phpstan-ignore-next-line
                columns: $fkColumns->pluck('column_name')->all(),
                // @phpstan-ignore-next-line
                referencedTable: $firstColumn->referenced_table,
                // @phpstan-ignore-next-line
                referencedColumns: $fkColumns->pluck('referenced_column')->all(),
                // @phpstan-ignore-next-line
                onUpdate: $firstColumn->on_update,
                // @phpstan-ignore-next-line
                onDelete: $firstColumn->on_delete
            );
        })->values();
    }

    protected function getConstraints(Connection $connection, string $tableName): Collection
    {
        $result = $connection->select("
            SELECT
                CONSTRAINT_NAME as name,
                CONSTRAINT_TYPE as type,
                CHECK_CLAUSE as check_clause
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
            LEFT JOIN INFORMATION_SCHEMA.CHECK_CONSTRAINTS cc
                ON tc.CONSTRAINT_NAME = cc.CONSTRAINT_NAME
                AND tc.CONSTRAINT_SCHEMA = cc.CONSTRAINT_SCHEMA
            WHERE tc.TABLE_SCHEMA = ?
            AND tc.TABLE_NAME = ?
            AND tc.CONSTRAINT_TYPE = 'CHECK'
        ", [$connection->getDatabaseName(), $tableName]);

        return collect($result)->map(fn ($constraint): ConstraintSchema => new ConstraintSchema(
            // @phpstan-ignore-next-line
            name: $constraint->name,
            type: 'check',
            column: null, // MySQL doesn't easily expose which column for CHECK
            // @phpstan-ignore-next-line
            expression: $constraint->check_clause,
            metadata: []
        ));
    }

    /**
     * Get table-specific metadata
     *
     * @return array{engine: null|string, collation: null|string, comment: null|string, auto_increment: null|int}
     */
    protected function getTableMetadata(Connection $connection, string $tableName): array
    {
        $result = $connection->selectOne('
            SELECT
                ENGINE as engine,
                TABLE_COLLATION as collation,
                TABLE_COMMENT as comment,
                AUTO_INCREMENT as auto_increment
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
        ', [$connection->getDatabaseName(), $tableName]);

        // @phpstan-ignore return.type
        return [
            // @phpstan-ignore-next-line
            'engine' => $result->engine ?? null,
            // @phpstan-ignore-next-line
            'collation' => $result->collation ?? null,
            // @phpstan-ignore-next-line
            'comment' => $result->comment ?: null,
            // @phpstan-ignore-next-line
            'auto_increment' => $result->auto_increment ? (int) $result->auto_increment : null,
        ];
    }

    /**
     * Parse default value from MySQL format
     */
    private function parseDefaultValue(?string $default): mixed
    {
        if ($default === null || mb_strtoupper($default) === 'NULL') {
            return null;
        }

        // Remove quotes for string defaults
        if (preg_match("/^'(.*)'$/", $default, $matches)) {
            return $matches[1];
        }

        // Handle numeric defaults
        if (is_numeric($default)) {
            return str_contains($default, '.') ? (float) $default : (int) $default;
        }

        // Return as-is for functions like CURRENT_TIMESTAMP
        return $default;
    }
}
