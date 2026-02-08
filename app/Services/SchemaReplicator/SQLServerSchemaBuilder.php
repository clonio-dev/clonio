<?php

declare(strict_types=1);

namespace App\Services\SchemaReplicator;

use App\Contracts\SchemaBuilderInterface;
use App\Data\ColumnSchema;
use App\Data\ForeignKeySchema;
use App\Data\IndexSchema;
use App\Data\TableSchema;

class SQLServerSchemaBuilder implements SchemaBuilderInterface
{
    public function buildCreateTable(TableSchema $table): string
    {
        $columns = [];
        foreach ($table->columns as $column) {
            $columns[] = $this->buildColumnDefinition($column);
        }

        $primaryKey = $table->getPrimaryKey();
        if ($primaryKey instanceof IndexSchema) {
            $columnList = implode(', ', array_map(fn (string $c): string => "[{$c}]", $primaryKey->columns));
            $columns[] = "PRIMARY KEY ({$columnList})";
        }

        $sql = "CREATE TABLE [{$table->name}] (\n";
        $sql .= '  ' . implode(",\n  ", $columns) . "\n";

        return $sql . ')';
    }

    public function buildCreateIndex(string $tableName, IndexSchema $index): string
    {
        $columnList = implode(', ', array_map(fn (string $c): string => "[{$c}]", $index->columns));

        if ($index->type === 'unique') {
            return "CREATE UNIQUE INDEX [{$index->name}] ON [{$tableName}] ({$columnList})";
        }

        return "CREATE INDEX [{$index->name}] ON [{$tableName}] ({$columnList})";
    }

    public function buildAddForeignKey(string $tableName, ForeignKeySchema $fk): string
    {
        $columns = implode(', ', array_map(fn (string $c): string => "[{$c}]", $fk->columns));
        $refColumns = implode(', ', array_map(fn (string $c): string => "[{$c}]", $fk->referencedColumns));

        // Map to SQL Server action names
        $onUpdate = str_replace(' ', '_', $fk->onUpdate);
        $onDelete = str_replace(' ', '_', $fk->onDelete);

        return "ALTER TABLE [{$tableName}] " .
            "ADD CONSTRAINT [{$fk->name}] " .
            "FOREIGN KEY ({$columns}) " .
            "REFERENCES [{$fk->referencedTable}] ({$refColumns}) " .
            "ON UPDATE {$onUpdate} " .
            "ON DELETE {$onDelete}";
    }

    public function buildAddColumn(string $tableName, ColumnSchema $column): string
    {
        return "ALTER TABLE [{$tableName}] ADD " . $this->buildColumnDefinition($column);
    }

    public function buildModifyColumn(string $tableName, ColumnSchema $column): string
    {
        return "ALTER TABLE [{$tableName}] ALTER COLUMN " . $this->buildColumnDefinition($column);
    }

    public function buildColumnDefinition(ColumnSchema $column): string
    {
        $def = "[{$column->name}] " . $this->buildDataType($column);

        if ($column->autoIncrement) {
            $def .= ' IDENTITY(1,1)';
        }

        if (! $column->nullable) {
            $def .= ' NOT NULL';
        }

        if ($column->default !== null && ! $column->autoIncrement) {
            $def .= ' DEFAULT ' . $this->formatDefaultValue($column->default);
        }

        return $def;
    }

    public function buildDataType(ColumnSchema $column): string
    {
        $type = mb_strtoupper($column->type);

        // Map some common types
        $typeMap = [
            'TINYINT' => 'TINYINT',
            'SMALLINT' => 'SMALLINT',
            'INT' => 'INT',
            'BIGINT' => 'BIGINT',
            'DECIMAL' => 'DECIMAL',
            'NUMERIC' => 'NUMERIC',
            'FLOAT' => 'FLOAT',
            'REAL' => 'REAL',
            'DOUBLE' => 'FLOAT',
            'DATE' => 'DATE',
            'TIME' => 'TIME',
            'DATETIME' => 'DATETIME2',
            'TIMESTAMP' => 'DATETIME2',
            'CHAR' => 'CHAR',
            'VARCHAR' => 'VARCHAR',
            'TEXT' => 'VARCHAR(MAX)',
            'NCHAR' => 'NCHAR',
            'NVARCHAR' => 'NVARCHAR',
            'NTEXT' => 'NVARCHAR(MAX)',
        ];

        $type = $typeMap[$type] ?? $type;

        if ($column->length !== null && ! str_contains($type, 'MAX')) {
            if ($column->scale !== null) {
                $type .= "({$column->length},{$column->scale})";
            } else {
                $type .= "({$column->length})";
            }
        }

        return $type;
    }

    protected function formatDefaultValue(mixed $value): string
    {
        if (is_string($value)) {
            return $this->quote($value);
        }
        if (is_numeric($value)) {
            return (string) $value;
        }

        return $value;
    }

    protected function quote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
