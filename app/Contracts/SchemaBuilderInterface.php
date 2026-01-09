<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\ColumnSchema;
use App\Data\ForeignKeySchema;
use App\Data\IndexSchema;
use App\Data\TableSchema;

/**
 * SchemaBuilder Interface
 *
 * Contract for generating SQL DDL statements for different DBMS.
 */
interface SchemaBuilderInterface
{
    /**
     * Build CREATE TABLE statement
     */
    public function buildCreateTable(TableSchema $table): string;

    /**
     * Build CREATE INDEX statement
     */
    public function buildCreateIndex(string $tableName, IndexSchema $index): string;

    /**
     * Build ADD FOREIGN KEY statement
     */
    public function buildAddForeignKey(string $tableName, ForeignKeySchema $fk): string;

    /**
     * Build ADD COLUMN statement
     */
    public function buildAddColumn(string $tableName, ColumnSchema $column): string;

    /**
     * Build MODIFY COLUMN statement
     */
    public function buildModifyColumn(string $tableName, ColumnSchema $column): string;

    /**
     * Build column definition (used in CREATE TABLE)
     */
    public function buildColumnDefinition(ColumnSchema $column): string;

    /**
     * Build data type definition
     */
    public function buildDataType(ColumnSchema $column): string;
}
