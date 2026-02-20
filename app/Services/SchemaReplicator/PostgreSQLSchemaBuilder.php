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
        if ($primaryKey instanceof IndexSchema) {
            $columnList = implode(', ', array_map(fn (string $c): string => sprintf('"%s"', $c), $primaryKey->columns));
            $columns[] = sprintf('PRIMARY KEY (%s)', $columnList);
        }

        $sql = "CREATE TABLE \"{$table->name}\" (\n";
        $sql .= '  ' . implode(",\n  ", $columns) . "\n";

        return $sql . ')';
    }

    public function buildCreateIndex(string $tableName, IndexSchema $index): string
    {
        $columnList = implode(', ', array_map(fn (string $c): string => sprintf('"%s"', $c), $index->columns));

        if ($index->type === 'unique') {
            return sprintf('CREATE UNIQUE INDEX "%s" ON "%s" (%s)', $index->name, $tableName, $columnList);
        }

        return sprintf('CREATE INDEX "%s" ON "%s" (%s)', $index->name, $tableName, $columnList);
    }

    public function buildAddForeignKey(string $tableName, ForeignKeySchema $fk): string
    {
        $columns = implode(', ', array_map(fn (string $c): string => sprintf('"%s"', $c), $fk->columns));
        $refColumns = implode(', ', array_map(fn (string $c): string => sprintf('"%s"', $c), $fk->referencedColumns));

        return sprintf('ALTER TABLE "%s" ', $tableName) .
            sprintf('ADD CONSTRAINT "%s" ', $fk->name) .
            sprintf('FOREIGN KEY (%s) ', $columns) .
            sprintf('REFERENCES "%s" (%s) ', $fk->referencedTable, $refColumns) .
            sprintf('ON UPDATE %s ', $fk->onUpdate) .
            ('ON DELETE ' . $fk->onDelete);
    }

    public function buildAddColumn(string $tableName, ColumnSchema $column): string
    {
        return sprintf('ALTER TABLE "%s" ADD COLUMN ', $tableName) . $this->buildColumnDefinition($column);
    }

    public function buildModifyColumn(string $tableName, ColumnSchema $column): string
    {
        // PostgreSQL requires multiple ALTER statements for different properties
        $statements = [];

        $statements[] = sprintf('ALTER TABLE "%s" ALTER COLUMN "%s" TYPE ', $tableName, $column->name) . $this->buildDataType($column);

        if ($column->nullable) {
            $statements[] = sprintf('ALTER TABLE "%s" ALTER COLUMN "%s" DROP NOT NULL', $tableName, $column->name);
        } else {
            $statements[] = sprintf('ALTER TABLE "%s" ALTER COLUMN "%s" SET NOT NULL', $tableName, $column->name);
        }

        if ($column->default !== null) {
            $statements[] = sprintf('ALTER TABLE "%s" ALTER COLUMN "%s" SET DEFAULT ', $tableName, $column->name) . $this->formatDefaultValue($column->default);
        }

        return implode(";\n", $statements);
    }

    public function buildColumnDefinition(ColumnSchema $column): string
    {
        $def = sprintf('"%s" ', $column->name) . $this->buildDataType($column);

        if (! $column->nullable) {
            $def .= ' NOT NULL';
        }

        if ($column->default !== null) {
            $def .= ' DEFAULT ' . $this->formatDefaultValue($column->default);
        }

        return $def;
    }

    public function buildDataType(ColumnSchema $column): string
    {
        $type = mb_strtoupper($column->type);

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
                $type .= sprintf('(%s,%s)', $column->length, $column->scale);
            } else {
                $type .= sprintf('(%s)', $column->length);
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
