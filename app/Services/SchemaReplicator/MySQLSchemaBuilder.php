<?php

declare(strict_types=1);

namespace App\Services\SchemaReplicator;

use App\Contracts\SchemaBuilderInterface;
use App\Data\ColumnSchema;
use App\Data\ForeignKeySchema;
use App\Data\IndexSchema;
use App\Data\TableSchema;

/**
 * MySQL SchemaBuilder
 *
 * Generates MySQL-specific DDL statements.
 */
class MySQLSchemaBuilder implements SchemaBuilderInterface
{
    public function buildCreateTable(TableSchema $table): string
    {
        $columns = [];
        foreach ($table->columns as $column) {
            $columns[] = $this->buildColumnDefinition($column);
        }

        // Add primary key
        $primaryKey = $table->getPrimaryKey();
        if ($primaryKey instanceof IndexSchema) {
            $columnList = implode(', ', array_map(fn (string $c): string => "`{$c}`", $primaryKey->columns));
            $columns[] = "PRIMARY KEY ({$columnList})";
        }

        // Add unique indexes
        foreach ($table->indexes as $index) {
            if ($index->type === 'unique') {
                $columnList = implode(', ', array_map(fn (string $c): string => "`{$c}`", $index->columns));
                $columns[] = "UNIQUE KEY `{$index->name}` ({$columnList})";
            }
        }

        $sql = "CREATE TABLE `{$table->name}` (\n";
        $sql .= '  ' . implode(",\n  ", $columns) . "\n";
        $sql .= ')';

        // Add table metadata
        if (isset($table->metadata['engine'])) {
            $sql .= " ENGINE={$table->metadata['engine']}";
        }
        if (isset($table->metadata['charset'])) {
            $sql .= " DEFAULT CHARSET={$table->metadata['charset']}";
        }
        if (isset($table->metadata['collation'])) {
            $sql .= " COLLATE={$table->metadata['collation']}";
        }

        return $sql;
    }

    public function buildCreateIndex(string $tableName, IndexSchema $index): string
    {
        $columnList = implode(', ', array_map(fn (string $c): string => "`{$c}`", $index->columns));

        $type = match ($index->type) {
            'unique' => 'UNIQUE INDEX',
            'fulltext' => 'FULLTEXT INDEX',
            'spatial' => 'SPATIAL INDEX',
            default => 'INDEX',
        };

        return "CREATE {$type} `{$index->name}` ON `{$tableName}` ({$columnList})";
    }

    public function buildAddForeignKey(string $tableName, ForeignKeySchema $fk): string
    {
        $columns = implode(', ', array_map(fn (string $c): string => "`{$c}`", $fk->columns));
        $refColumns = implode(', ', array_map(fn (string $c): string => "`{$c}`", $fk->referencedColumns));

        return "ALTER TABLE `{$tableName}` " .
            "ADD CONSTRAINT `{$fk->name}` " .
            "FOREIGN KEY ({$columns}) " .
            "REFERENCES `{$fk->referencedTable}` ({$refColumns}) " .
            "ON UPDATE {$fk->onUpdate} " .
            "ON DELETE {$fk->onDelete}";
    }

    public function buildAddColumn(string $tableName, ColumnSchema $column): string
    {
        return "ALTER TABLE `{$tableName}` ADD COLUMN " . $this->buildColumnDefinition($column);
    }

    public function buildModifyColumn(string $tableName, ColumnSchema $column): string
    {
        return "ALTER TABLE `{$tableName}` MODIFY COLUMN " . $this->buildColumnDefinition($column);
    }

    public function buildColumnDefinition(ColumnSchema $column): string
    {
        $def = "`{$column->name}` " . $this->buildDataType($column);

        if (! $column->nullable) {
            $def .= ' NOT NULL';
        }

        if ($column->autoIncrement) {
            $def .= ' AUTO_INCREMENT';
        }

        if ($column->default !== null) {
            $def .= ' DEFAULT ' . $this->formatDefaultValue($column->default);
        }

        if ($column->comment) {
            $def .= ' COMMENT ' . $this->quote($column->comment);
        }

        return $def;
    }

    public function buildDataType(ColumnSchema $column): string
    {
        $type = mb_strtoupper($column->type);

        if (($type === 'SET' || $type === 'ENUM') && isset($column->metadata['column_type'])) {
            return $column->metadata['column_type']
                // special trick to support NULL on SET/ENUM
                . ($column->nullable && $column->default === null ? ' DEFAULT NULL' : '');
        }

        if ($column->length !== null) {
            if ($column->scale !== null) {
                $type .= "({$column->length},{$column->scale})";
            } else {
                $type .= "({$column->length})";
            }
        }

        if ($column->unsigned) {
            $type .= ' UNSIGNED';
        }

        return $type;
    }

    protected function formatDefaultValue(mixed $value): string
    {
        if ($value === 'CURRENT_TIMESTAMP') {
            return $value;
        }

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
