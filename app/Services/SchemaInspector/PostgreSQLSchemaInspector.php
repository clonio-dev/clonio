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

/**
 * PostgreSQL SchemaInspector
 *
 * Inspects PostgreSQL database schema using pg_catalog and information_schema.
 */
class PostgreSQLSchemaInspector extends AbstractSchemaInspector
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
     * @return array<int, mixed>
     */
    public function getTableNames(Connection $connection): array
    {
        /** @var array<int, \stdClass> $result */
        $result = $connection->select("
            SELECT tablename
            FROM pg_catalog.pg_tables
            WHERE schemaname = 'public'
            ORDER BY tablename
        ");

        // phpstan-ignore return.type
        return array_map(fn($row) => $row->tablename, $result);
    }

    public function getDatabaseMetadata(Connection $connection): array
    {
        $version = $connection->selectOne("SELECT version() as version");

        return [
            // phpstan-ignore-next-line
            'version' => $version->version ?? null,
            // phpstan-ignore property.nonObject
            'encoding' => $connection->selectOne("SHOW server_encoding")->server_encoding ?? null,
            // phpstan-ignore-next-line
            'collation' => $connection->selectOne("SHOW lc_collate")->lc_collate ?? null,
        ];
    }

    protected function getColumns(Connection $connection, string $tableName): Collection
    {
        $result = $connection->select("
            SELECT
                c.column_name as name,
                c.data_type,
                c.is_nullable,
                c.column_default,
                c.character_maximum_length,
                c.numeric_precision,
                c.numeric_scale,
                c.udt_name,
                pg_catalog.col_description(
                    (quote_ident(c.table_schema)||'.'||quote_ident(c.table_name))::regclass::oid,
                    c.ordinal_position
                ) as comment
            FROM information_schema.columns c
            WHERE c.table_schema = 'public'
            AND c.table_name = ?
            ORDER BY c.ordinal_position
        ", [$tableName]);

        return collect($result)->map(function ($column) {
            // Check if auto-increment (SERIAL types or sequences)
            $autoIncrement = str_contains($column->column_default ?? '', 'nextval');

            // Parse actual type (PostgreSQL returns composite types differently)
            $type = $this->normalizeType($column->data_type, $column->udt_name);

            return new ColumnSchema(
                name: $column->name,
                type: $type,
                nullable: $column->is_nullable === 'YES',
                default: $this->parseDefaultValue($column->column_default),
                length: $column->character_maximum_length ? (int) $column->character_maximum_length : null,
                scale: $column->numeric_scale ? (int) $column->numeric_scale : null,
                autoIncrement: $autoIncrement,
                unsigned: false, // PostgreSQL doesn't have unsigned
                charset: null,
                collation: null,
                comment: $column->comment ?: null,
                metadata: [
                    'data_type' => $column->data_type,
                    'udt_name' => $column->udt_name,
                ]
            );
        });
    }

    protected function getTableMetrics(Connection $connection, string $tableName): TableMetricsData
    {
        $rowCount = $connection->selectOne("
            SELECT reltuples::bigint AS row_count
            FROM pg_class
            WHERE relname = ?
        ", [$tableName])->row_count ?? 0;

        // Data Size (nur Table Data, ohne Indexes)
        $dataSize = $connection->selectOne("
            SELECT pg_relation_size(?)::bigint AS data_size
        ", [$tableName])->data_size ?? 0;

        return new TableMetricsData(
            rowsCount: (int) $rowCount,
            dataSizeInBytes: (int) $dataSize,
        );
    }

    protected function getIndexes(Connection $connection, string $tableName): Collection
    {
        $result = $connection->select("
            SELECT
                i.relname as index_name,
                ix.indisunique as is_unique,
                ix.indisprimary as is_primary,
                array_agg(a.attname ORDER BY array_position(ix.indkey, a.attnum)) as columns,
                am.amname as index_type
            FROM pg_class t
            JOIN pg_index ix ON t.oid = ix.indrelid
            JOIN pg_class i ON i.oid = ix.indexrelid
            JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(ix.indkey)
            JOIN pg_am am ON i.relam = am.oid
            WHERE t.relkind = 'r'
            AND t.relname = ?
            AND t.relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = 'public')
            GROUP BY i.relname, ix.indisunique, ix.indisprimary, am.amname
            ORDER BY i.relname
        ", [$tableName]);

        return collect($result)->map(function ($index) {
            // Determine type
            $type = 'index';
            if ($index->is_primary) {
                $type = 'primary';
            } elseif ($index->is_unique) {
                $type = 'unique';
            }

            // Parse PostgreSQL array format {col1,col2}
            $columns = $this->parsePostgresArray($index->columns);

            return new IndexSchema(
                name: $index->index_name,
                type: $type,
                columns: $columns,
                metadata: [
                    'index_type' => $index->index_type,
                ]
            );
        });
    }

    protected function getForeignKeys(Connection $connection, string $tableName): Collection
    {
        $result = $connection->select("
            SELECT
                tc.constraint_name,
                kcu.column_name,
                ccu.table_name AS referenced_table,
                ccu.column_name AS referenced_column,
                rc.update_rule,
                rc.delete_rule
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
                ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage ccu
                ON ccu.constraint_name = tc.constraint_name
                AND ccu.table_schema = tc.table_schema
            JOIN information_schema.referential_constraints rc
                ON rc.constraint_name = tc.constraint_name
                AND rc.constraint_schema = tc.table_schema
            WHERE tc.constraint_type = 'FOREIGN KEY'
            AND tc.table_schema = 'public'
            AND tc.table_name = ?
            ORDER BY tc.constraint_name, kcu.ordinal_position
        ", [$tableName]);

        $grouped = collect($result)->groupBy('constraint_name');

        return $grouped->map(function ($fkColumns, $constraintName) use ($tableName) {
            $firstColumn = $fkColumns->first();

            return new ForeignKeySchema(
                name: $constraintName,
                table: $tableName,
                columns: $fkColumns->pluck('column_name')->all(),
                referencedTable: $firstColumn->referenced_table,
                referencedColumns: $fkColumns->pluck('referenced_column')->all(),
                onUpdate: $firstColumn->update_rule,
                onDelete: $firstColumn->delete_rule
            );
        })->values();
    }

    protected function getConstraints(Connection $connection, string $tableName): Collection
    {
        $result = $connection->select("
            SELECT
                con.conname as name,
                con.contype as type,
                pg_get_constraintdef(con.oid) as definition
            FROM pg_catalog.pg_constraint con
            JOIN pg_catalog.pg_class rel ON rel.oid = con.conrelid
            JOIN pg_catalog.pg_namespace nsp ON nsp.oid = connamespace
            WHERE nsp.nspname = 'public'
            AND rel.relname = ?
            AND con.contype = 'c'  -- CHECK constraints
        ", [$tableName]);

        return collect($result)->map(function ($constraint) {
            return new ConstraintSchema(
                name: $constraint->name,
                type: 'check',
                column: null,
                expression: $constraint->definition,
                metadata: []
            );
        });
    }

    /**
     * Get table-specific metadata
     *
     * @return array{comment: null|string}
     */
    protected function getTableMetadata(Connection $connection, string $tableName): array
    {
        $result = $connection->selectOne("
            SELECT
                obj_description((quote_ident('public')||'.'||quote_ident(?))::regclass) as comment
        ", [$tableName]);

        return [
            'comment' => $result->comment ?: null,
        ];
    }

    /**
     * Normalize PostgreSQL type names to standard types
     */
    private function normalizeType(string $dataType, string $udtName): string
    {
        // Map PostgreSQL-specific types to standard types
        $typeMap = [
            'character varying' => 'varchar',
            'character' => 'char',
            'timestamp without time zone' => 'timestamp',
            'timestamp with time zone' => 'timestamptz',
            'time without time zone' => 'time',
            'time with time zone' => 'timetz',
            'double precision' => 'double',
            'real' => 'float',
        ];

        return $typeMap[$dataType] ?? $dataType;
    }

    /**
     * Parse PostgreSQL array format {item1,item2}
     */
    private function parsePostgresArray(string $array): array
    {
        $array = trim($array, '{}');
        return array_map('trim', explode(',', $array));
    }

    /**
     * Parse default value from PostgreSQL format
     */
    private function parseDefaultValue(?string $default): mixed
    {
        if ($default === null || strtoupper($default) === 'NULL') {
            return null;
        }

        // Skip sequences/functions
        if (str_contains($default, 'nextval') ||
            str_contains($default, 'CURRENT_') ||
            str_contains($default, '()')) {
            return null;
        }

        // Remove type casts like ::integer
        $default = preg_replace('/::[a-z]+/', '', $default);

        // Remove quotes
        if (preg_match("/^'(.*)'$/", $default, $matches)) {
            return $matches[1];
        }

        // Handle numeric
        if (is_numeric($default)) {
            return str_contains($default, '.') ? (float) $default : (int) $default;
        }

        return $default;
    }
}
