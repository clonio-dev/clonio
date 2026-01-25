<?php

declare(strict_types=1);

namespace App\Services\SchemaReplicator;

use App\Contracts\SchemaBuilderInterface;
use App\Data\ColumnSchema;
use App\Data\ForeignKeySchema;
use App\Data\IndexSchema;
use App\Data\TableSchema;
use RuntimeException;

class SQLiteSchemaBuilder implements SchemaBuilderInterface
{
    public function buildCreateTable(TableSchema $table): string
    {
        $definitions = [];

        foreach ($table->columns as $column) {
            $definitions[] = $this->buildColumnDefinition($column);
        }

        $primaryKey = $table->getPrimaryKey();
        if ($primaryKey && count($primaryKey->columns) > 1) {
            $columnList = implode(', ', $primaryKey->columns);
            $definitions[] = "PRIMARY KEY ({$columnList})";
        }

        // Include foreign keys in CREATE TABLE (SQLite requires this)
        foreach ($table->foreignKeys as $fk) {
            $definitions[] = $this->buildForeignKeyConstraint($fk);
        }

        $sql = "CREATE TABLE \"{$table->name}\" (\n";
        $sql .= '  ' . implode(",\n  ", $definitions) . "\n";

        return $sql . ')';
    }

    public function buildCreateIndex(string $tableName, IndexSchema $index): string
    {
        $columnList = implode(', ', $index->columns);

        if ($index->type === 'unique') {
            return "CREATE UNIQUE INDEX \"{$index->name}\" ON \"{$tableName}\" ({$columnList})";
        }

        return "CREATE INDEX \"{$index->name}\" ON \"{$tableName}\" ({$columnList})";
    }

    public function buildAddForeignKey(string $tableName, ForeignKeySchema $fk): string
    {
        // SQLite doesn't support adding foreign keys to existing tables
        // This would require recreating the table
        throw new RuntimeException(
            'SQLite does not support adding foreign keys to existing tables. ' .
            'Table must be recreated with foreign key definition.'
        );
    }

    public function buildAddColumn(string $tableName, ColumnSchema $column): string
    {
        return "ALTER TABLE \"{$tableName}\" ADD COLUMN " . $this->buildColumnDefinition($column);
    }

    public function buildModifyColumn(string $tableName, ColumnSchema $column): string
    {
        // SQLite doesn't support modifying columns
        // This would require recreating the table
        throw new RuntimeException(
            'SQLite does not support modifying columns. ' .
            'Table must be recreated with new column definition.'
        );
    }

    public function buildColumnDefinition(ColumnSchema $column): string
    {
        $def = "\"{$column->name}\" " . $this->buildDataType($column);

        // Primary key for single-column auto-increment
        $isPrimaryAutoIncrement = $column->autoIncrement && mb_strtoupper($column->type) === 'INTEGER';
        if ($isPrimaryAutoIncrement) {
            $def .= ' PRIMARY KEY AUTOINCREMENT';
        }

        if (! $column->nullable && ! $isPrimaryAutoIncrement) {
            $def .= ' NOT NULL';
        }

        if ($column->default !== null && ! $isPrimaryAutoIncrement) {
            $def .= ' DEFAULT ' . $this->formatDefaultValue($column->default);
        }

        return $def;
    }

    public function buildDataType(ColumnSchema $column): string
    {
        $type = mb_strtoupper($column->type);

        if ($column->length !== null && ! in_array($type, ['TEXT', 'BLOB'])) {
            if ($column->scale !== null) {
                $type .= "({$column->length},{$column->scale})";
            } else {
                $type .= "({$column->length})";
            }
        }

        return $type;
    }

    protected function buildForeignKeyConstraint(ForeignKeySchema $fk): string
    {
        $localColumns = implode(', ', array_map(fn (string $c): string => "\"{$c}\"", $fk->columns));
        $referencedColumns = implode(', ', array_map(fn (string $c): string => "\"{$c}\"", $fk->referencedColumns));

        $sql = "FOREIGN KEY ({$localColumns}) REFERENCES \"{$fk->referencedTable}\" ({$referencedColumns})";

        if ($fk->onUpdate && $fk->onUpdate !== 'NO ACTION') {
            $sql .= " ON UPDATE {$fk->onUpdate}";
        }

        if ($fk->onDelete && $fk->onDelete !== 'NO ACTION') {
            $sql .= " ON DELETE {$fk->onDelete}";
        }

        return $sql;
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
