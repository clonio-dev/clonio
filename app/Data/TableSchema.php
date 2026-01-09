<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;

/**
 * TableSchema DTO
 *
 * Represents a single database table including its columns,
 * indexes, foreign keys, and constraints.
 */
final readonly class TableSchema
{
    /**
     * @param  string  $name  Table name
     * @param  Collection<int, ColumnSchema>  $columns  Collection of column schemas
     * @param  Collection<int, IndexSchema>  $indexes  Collection of index schemas
     * @param  Collection<int, ForeignKeySchema>  $foreignKeys  Collection of foreign key schemas
     * @param  Collection<int, ConstraintSchema>  $constraints  Collection of constraint schemas
     * @param  array<string, mixed>  $metadata  Additional metadata (engine, collation, etc.)
     */
    public function __construct(
        public string $name,
        public Collection $columns,
        public Collection $indexes,
        public Collection $foreignKeys,
        public Collection $constraints,
        public array $metadata = [],
        public ?TableMetricsData $metricsData,
    ) {}

    /**
     * Create from array
     *
     * @param  array{name: string, columns: array, indexes: array, foreign_keys: array, constraints: array, metadata: null|array<string, mixed>}  $data
     */
    // @phpstan-ignore-next-line
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            // @phpstan-ignore-next-line
            columns: collect($data['columns'])->map(fn (array $c): ColumnSchema => ColumnSchema::fromArray($c)),
            // @phpstan-ignore-next-line
            indexes: collect($data['indexes'])->map(fn (array $i): IndexSchema => IndexSchema::fromArray($i)),
            // @phpstan-ignore-next-line
            foreignKeys: collect($data['foreign_keys'])->map(fn (array $fk): ForeignKeySchema => ForeignKeySchema::fromArray($fk)),
            // @phpstan-ignore-next-line
            constraints: collect($data['constraints'])->map(fn (array $c): ConstraintSchema => ConstraintSchema::fromArray($c)),
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Get a specific column by name
     */
    public function getColumn(string $columnName): ?ColumnSchema
    {
        return $this->columns->firstWhere('name', $columnName);
    }

    /**
     * Check if column exists
     */
    public function hasColumn(string $columnName): bool
    {
        return $this->columns->contains('name', $columnName);
    }

    /**
     * Get all column names
     *
     * @return Collection<(int|string), mixed>
     */
    public function getColumnNames(): Collection
    {
        return $this->columns->pluck('name');
    }

    /**
     * Get primary key column(s)
     */
    public function getPrimaryKey(): ?IndexSchema
    {
        return $this->indexes->firstWhere('type', 'primary');
    }

    /**
     * Get all foreign keys
     *
     * @return Collection<int, ForeignKeySchema>
     */
    public function getForeignKeys(): Collection
    {
        return $this->foreignKeys;
    }

    /**
     * Get foreign keys referencing specific table
     *
     * @return Collection<int, ForeignKeySchema>
     */
    public function getForeignKeysReferencingTable(string $tableName): Collection
    {
        return $this->foreignKeys->where('referencedTable', $tableName);
    }

    /**
     * Convert to array for serialization
     *
     * @return array{name: string, columns: array{name: string, type: string, nullable: bool, default: mixed, length: null|int, scale: null|int, auto_increment: bool, unsigned: bool, charset: null|string, collation: null|string, comment: null|string, metadata: array<string, mixed>}[], indexes: array{name: string, type: string, columns: array<string>, metadata: null|array<string, mixed>}[], foreign_keys: array{name: string, table: string, columns: array<string>, referenced_table: string, referenced_columns: array<string>, on_update: 'CASCADE'|'SET NULL'|'RESTRICT'|'NO ACTION', on_delete: 'CASCADE'|'SET NULL'|'RESTRICT'|'NO ACTION', metadata: array<string, mixed>}[], constraints: array{name: string, type: string, column: null|string, expression: null|string, metadata: null|array<string, mixed>}[], metadata: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'columns' => $this->columns->map->toArray()->all(),
            'indexes' => $this->indexes->map->toArray()->all(),
            'foreign_keys' => $this->foreignKeys->map->toArray()->all(),
            'constraints' => $this->constraints->map->toArray()->all(),
            'metadata' => $this->metadata,
        ];
    }
}
