<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Data\DatabaseSchema;
use App\Data\TableSchema;
use Illuminate\Database\Connection;

/**
 * SchemaInspector Interface
 *
 * Contract for database schema inspection across different DBMS.
 * All implementations must return standardized DTOs.
 */
interface SchemaInspectorInterface
{
    /**
     * Get complete database schema
     */
    public function getDatabaseSchema(Connection $connection, ?string $databaseName = null): DatabaseSchema;

    /**
     * Get schema for a specific table
     */
    public function getTableSchema(Connection $connection, string $tableName): TableSchema;

    /**
     * Get list of all table names in database
     *
     * @return string[]
     */
    public function getTableNames(Connection $connection): array;

    /**
     * Check if table exists
     */
    public function tableExists(Connection $connection, string $tableName): bool;

    /**
     * Get database metadata (version, charset, etc.)
     *
     * @return array{version: null|string, charset: null|string, collation: null|string}
     */
    public function getDatabaseMetadata(Connection $connection): array;
}
