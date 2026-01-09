<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;

/**
 * DatabaseSchema DTO
 *
 * Represents the complete schema of a database including all tables,
 * their columns, indexes, foreign keys, and constraints.
 */
final readonly class DatabaseSchema
{
    /**
     * @param  string  $databaseName  Name of the database
     * @param  string  $databaseType  Type (mysql, pgsql, sqlite, sqlsrv)
     * @param  Collection<int, TableSchema>  $tables  Collection of table schemas
     * @param  array<string, mixed>  $metadata  Additional metadata (version, charset, etc.)
     */
    public function __construct(
        public string $databaseName,
        public string $databaseType,
        public Collection $tables,
        public array $metadata = []
    ) {}

    /**
     * Create from array
     *
     * @param  array{database_name: string, database_type: string, tables: array, metadata: null|array<string, mixed>}  $data
     */
    // @phpstan-ignore-next-line
    public static function fromArray(array $data): self
    {
        return new self(
            databaseName: $data['database_name'],
            databaseType: $data['database_type'],
            // @phpstan-ignore-next-line
            tables: collect($data['tables'])->map(fn (array $t): TableSchema => TableSchema::fromArray($t)),
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Get a specific table schema by name
     */
    public function getTable(string $tableName): ?TableSchema
    {
        return $this->tables->firstWhere('name', $tableName);
    }

    /**
     * Check if table exists in schema
     */
    public function hasTable(string $tableName): bool
    {
        return $this->tables->contains('name', $tableName);
    }

    /**
     * Get all table names
     *
     * @return Collection<(int|string), mixed>
     */
    public function getTableNames(): Collection
    {
        return $this->tables->pluck('name');
    }

    /**
     * Count total number of tables
     */
    public function getTableCount(): int
    {
        return $this->tables->count();
    }

    /**
     * Convert to array for serialization
     *
     * @return array{database_name: string, database_type: string, tables: array<array{name: string, columns: array, indexes: array, foreign_keys: array, constraints: array, metadata: array<string, mixed>}>, metadata: array<string, mixed>}
     */
    // @phpstan-ignore-next-line
    public function toArray(): array
    {
        return [
            'database_name' => $this->databaseName,
            'database_type' => $this->databaseType,
            'tables' => $this->tables->map->toArray()->all(),
            'metadata' => $this->metadata,
        ];
    }
}
