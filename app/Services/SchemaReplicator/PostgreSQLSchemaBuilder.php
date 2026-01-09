<?php

declare(strict_types=1);

namespace App\Services\SchemaReplicator;

use App\Contracts\SchemaBuilderInterface;
use App\Data\ColumnSchema;
use App\Data\ForeignKeySchema;
use App\Data\IndexSchema;
use App\Data\TableSchema;

class PostgreSQLSchemaBuilder implements SchemaBuilderInterface
{
    public function buildCreateTable(TableSchema $table): string
    {
        $columns = [];
        foreach ($table->columns as $column) {
            $columns[] = $this->buildColumnDefinition($column);
        }

        $primaryKey = $table->getPrimaryKey();
        if ($primaryKey) {
            $columnList = implode(', ', array_map(fn($c) => "\"{$c}\"", $primaryKey->columns));
            $columns[] = "PRIMARY KEY ({$columnList})";
        }

        $sql = "CREATE TABLE \"{$table->name}\" (\n";
        $sql .= "  " . implode(",\n  ", $columns) . "\n";
        $sql .= ")";

        return $sql;
    }

    public function buildCreateIndex(string $tableName, IndexSchema $index): string
    {
        $columnList = implode(', ', array_map(fn($c) => "\"{$c}\"", $index->columns));

        if ($index->type === 'unique') {
            return "CREATE UNIQUE INDEX \"{$index->name}\" ON \"{$tableName}\" ({$columnList})";
        }

        return "CREATE INDEX \"{$index->name}\" ON \"{$tableName}\" ({$columnList})";
    }

    public function buildAddForeignKey(string $tableName, ForeignKeySchema $fk): string
    {
        $columns = implode(', ', array_map(fn($c) => "\"{$c}\"", $fk->columns));
        $refColumns = implode(', ', array_map(fn($c) => "\"{$c}\"", $fk->referencedColumns));

        return "ALTER TABLE \"{$tableName}\" " .
            "ADD CONSTRAINT \"{$fk->name}\" " .
            "FOREIGN KEY ({$columns}) " .
            "REFERENCES \"{$fk->referencedTable}\" ({$refColumns}) " .
            "ON UPDATE {$fk->onUpdate} " .
            "ON DELETE {$fk->onDelete}";
    }

    public function buildAddColumn(string $tableName, ColumnSchema $column): string
    {
        return "ALTER TABLE \"{$tableName}\" ADD COLUMN " . $this->buildColumnDefinition($column);
    }

    public function buildModifyColumn(string $tableName, ColumnSchema $column): string
    {
        // PostgreSQL requires multiple ALTER statements for different properties
        $statements = [];

        $statements[] = "ALTER TABLE \"{$tableName}\" ALTER COLUMN \"{$column->name}\" TYPE " . $this->buildDataType($column);

        if ($column->nullable) {
            $statements[] = "ALTER TABLE \"{$tableName}\" ALTER COLUMN \"{$column->name}\" DROP NOT NULL";
        } else {
            $statements[] = "ALTER TABLE \"{$tableName}\" ALTER COLUMN \"{$column->name}\" SET NOT NULL";
        }

        if ($column->default !== null) {
            $statements[] = "ALTER TABLE \"{$tableName}\" ALTER COLUMN \"{$column->name}\" SET DEFAULT " . $this->formatDefaultValue($column->default);
        }

        return implode(";\n", $statements);
    }

    public function buildColumnDefinition(ColumnSchema $column): string
    {
        $def = "\"{$column->name}\" " . $this->buildDataType($column);

        if (!$column->nullable) {
            $def .= " NOT NULL";
        }

        if ($column->default !== null) {
            $def .= " DEFAULT " . $this->formatDefaultValue($column->default);
        }

        return $def;
    }

    public function buildDataType(ColumnSchema $column): string
    {
        $type = strtoupper($column->type);

        // Handle auto increment (SERIAL types)
        if ($column->autoIncrement) {
            return match ($type) {
                'BIGINT' => 'BIGSERIAL',
                'SMALLINT' => 'SMALLSERIAL',
                default => 'SERIAL',
            };
        }

        if ($column->length !== null) {
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
