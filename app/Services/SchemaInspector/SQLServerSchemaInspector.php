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
 * SQL Server SchemaInspector
 *
 * Inspects SQL Server database schema using sys catalog views.
 */
class SQLServerSchemaInspector extends AbstractSchemaInspector
{
    public function getTableSchema(Connection $connection, string $tableName): TableSchema
    {
        return new TableSchema(
            name: $tableName,
            columns: $this->getColumns($connection, $tableName),
            indexes: $this->getIndexes($connection, $tableName),
            foreignKeys: $this->getForeignKeys($connection, $tableName),
            constraints: $this->getConstraints($connection, $tableName),
            metadata: $this->getTableMetadata($connection, $tableName)
        );
    }

    public function getTableNames(Connection $connection): array
    {
        $result = $connection->select("
            SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_TYPE = 'BASE TABLE'
            AND TABLE_SCHEMA = 'dbo'
            ORDER BY TABLE_NAME
        ");

        return array_map(fn($row) => $row->TABLE_NAME, $result);
    }

    public function getDatabaseMetadata(Connection $connection): array
    {
        $version = $connection->selectOne("SELECT @@VERSION as version");
        $dbInfo = $connection->selectOne("
            SELECT
                DATABASEPROPERTYEX(DB_NAME(), 'Collation') as collation,
                DATABASEPROPERTYEX(DB_NAME(), 'Edition') as edition
        ");

        return [
            'version' => $version->version ?? null,
            'collation' => $dbInfo->collation ?? null,
            'edition' => $dbInfo->edition ?? null,
        ];
    }

    protected function getColumns(Connection $connection, string $tableName): Collection
    {
        $result = $connection->select("
            SELECT
                c.COLUMN_NAME as name,
                c.DATA_TYPE as data_type,
                c.IS_NULLABLE as is_nullable,
                c.COLUMN_DEFAULT as default_value,
                c.CHARACTER_MAXIMUM_LENGTH as max_length,
                c.NUMERIC_PRECISION as numeric_precision,
                c.NUMERIC_SCALE as numeric_scale,
                COLUMNPROPERTY(OBJECT_ID(c.TABLE_SCHEMA + '.' + c.TABLE_NAME), c.COLUMN_NAME, 'IsIdentity') as is_identity,
                ep.value as comment
            FROM INFORMATION_SCHEMA.COLUMNS c
            LEFT JOIN sys.extended_properties ep
                ON ep.major_id = OBJECT_ID(c.TABLE_SCHEMA + '.' + c.TABLE_NAME)
                AND ep.minor_id = COLUMNPROPERTY(OBJECT_ID(c.TABLE_SCHEMA + '.' + c.TABLE_NAME), c.COLUMN_NAME, 'ColumnId')
                AND ep.name = 'MS_Description'
            WHERE c.TABLE_SCHEMA = 'dbo'
            AND c.TABLE_NAME = ?
            ORDER BY c.ORDINAL_POSITION
        ", [$tableName]);

        return collect($result)->map(function ($column) {
            return new ColumnSchema(
                name: $column->name,
                type: $column->data_type,
                nullable: $column->is_nullable === 'YES',
                default: $this->parseDefaultValue($column->default_value),
                length: $column->max_length ? (int) $column->max_length : null,
                scale: $column->numeric_scale ? (int) $column->numeric_scale : null,
                autoIncrement: $column->is_identity == 1,
                unsigned: false, // SQL Server doesn't have unsigned
                charset: null,
                collation: null,
                comment: $column->comment ?: null,
                metadata: [
                    'data_type' => $column->data_type,
                ]
            );
        });
    }

    protected function getIndexes(Connection $connection, string $tableName): Collection
    {
        $result = $connection->select("
            SELECT
                i.name as index_name,
                i.is_unique,
                i.is_primary_key,
                i.type_desc,
                STRING_AGG(c.name, ',') WITHIN GROUP (ORDER BY ic.key_ordinal) as columns
            FROM sys.indexes i
            JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
            JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
            WHERE i.object_id = OBJECT_ID('dbo.' + ?)
            AND i.name IS NOT NULL
            GROUP BY i.name, i.is_unique, i.is_primary_key, i.type_desc
            ORDER BY i.name
        ", [$tableName]);

        return collect($result)->map(function ($index) {
            // Determine type
            $type = 'index';
            if ($index->is_primary_key) {
                $type = 'primary';
            } elseif ($index->is_unique) {
                $type = 'unique';
            }

            // Parse comma-separated columns
            $columns = explode(',', $index->columns);

            return new IndexSchema(
                name: $index->index_name,
                type: $type,
                columns: $columns,
                metadata: [
                    'type_desc' => $index->type_desc,
                ]
            );
        });
    }

    protected function getForeignKeys(Connection $connection, string $tableName): Collection
    {
        $result = $connection->select("
            SELECT
                fk.name as constraint_name,
                STRING_AGG(c.name, ',') WITHIN GROUP (ORDER BY fkc.constraint_column_id) as columns,
                OBJECT_NAME(fk.referenced_object_id) as referenced_table,
                STRING_AGG(rc.name, ',') WITHIN GROUP (ORDER BY fkc.constraint_column_id) as referenced_columns,
                fk.update_referential_action_desc as update_rule,
                fk.delete_referential_action_desc as delete_rule
            FROM sys.foreign_keys fk
            JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
            JOIN sys.columns c ON fkc.parent_object_id = c.object_id AND fkc.parent_column_id = c.column_id
            JOIN sys.columns rc ON fkc.referenced_object_id = rc.object_id AND fkc.referenced_column_id = rc.column_id
            WHERE fk.parent_object_id = OBJECT_ID('dbo.' + ?)
            GROUP BY fk.name, fk.referenced_object_id, fk.update_referential_action_desc, fk.delete_referential_action_desc
            ORDER BY fk.name
        ", [$tableName]);

        return collect($result)->map(function ($fk) use ($tableName) {
            return new ForeignKeySchema(
                name: $fk->constraint_name,
                table: $tableName,
                columns: explode(',', $fk->columns),
                referencedTable: $fk->referenced_table,
                referencedColumns: explode(',', $fk->referenced_columns),
                onUpdate: $this->normalizeReferentialAction($fk->update_rule),
                onDelete: $this->normalizeReferentialAction($fk->delete_rule)
            );
        });
    }

    protected function getConstraints(Connection $connection, string $tableName): Collection
    {
        $result = $connection->select("
            SELECT
                cc.name,
                cc.definition
            FROM sys.check_constraints cc
            WHERE cc.parent_object_id = OBJECT_ID('dbo.' + ?)
            ORDER BY cc.name
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
     */
    protected function getTableMetadata(Connection $connection, string $tableName): array
    {
        $result = $connection->selectOne("
            SELECT
                ep.value as comment
            FROM sys.tables t
            LEFT JOIN sys.extended_properties ep
                ON ep.major_id = t.object_id
                AND ep.minor_id = 0
                AND ep.name = 'MS_Description'
            WHERE t.schema_id = SCHEMA_ID('dbo')
            AND t.name = ?
        ", [$tableName]);

        return [
            'comment' => $result->comment ?: null,
        ];
    }

    /**
     * Normalize SQL Server referential action to standard format
     */
    private function normalizeReferentialAction(string $action): string
    {
        $actionMap = [
            'NO_ACTION' => 'NO ACTION',
            'CASCADE' => 'CASCADE',
            'SET_NULL' => 'SET NULL',
            'SET_DEFAULT' => 'SET DEFAULT',
        ];

        return $actionMap[$action] ?? $action;
    }

    /**
     * Parse default value from SQL Server format
     */
    private function parseDefaultValue(?string $default): mixed
    {
        if ($default === null || strtoupper($default) === 'NULL') {
            return null;
        }

        // Remove outer parentheses: ((value))
        $default = preg_replace('/^\((.*)\)$/', '$1', trim($default));
        $default = preg_replace('/^\((.*)\)$/', '$1', $default);

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
