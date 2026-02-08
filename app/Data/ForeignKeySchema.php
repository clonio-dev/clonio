<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Represents a foreign key relationship between two tables.
 */
final readonly class ForeignKeySchema
{
    /**
     * @param  string  $name  Foreign key constraint name
     * @param  string  $table  The table containing the foreign key
     * @param  array<string>  $columns  Column(s) in the local table
     * @param  string  $referencedTable  The referenced table
     * @param  array<string>  $referencedColumns  Column(s) in the referenced table
     * @param  'CASCADE'|'SET NULL'|'RESTRICT'|'NO ACTION'  $onUpdate  Action on UPDATE: 'CASCADE', 'SET NULL', 'RESTRICT', 'NO ACTION'
     * @param  'CASCADE'|'SET NULL'|'RESTRICT'|'NO ACTION'  $onDelete  Action on DELETE: 'CASCADE', 'SET NULL', 'RESTRICT', 'NO ACTION'
     * @param  array<string, mixed>  $metadata  Additional DB-specific metadata
     */
    public function __construct(
        public string $name,
        public string $table,
        public array $columns,
        public string $referencedTable,
        public array $referencedColumns,
        public string $onUpdate = 'RESTRICT',
        public string $onDelete = 'RESTRICT',
        public array $metadata = []
    ) {}

    /**
     * Create from array
     *
     * @param  array{name: string, table: string, columns: array<string>, referenced_table: string, referenced_columns: array<string>, on_update: null|'CASCADE'|'SET NULL'|'RESTRICT'|'NO ACTION', on_delete: null|'CASCADE'|'SET NULL'|'RESTRICT'|'NO ACTION', metadata: null|array<string, mixed>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            table: $data['table'],
            columns: $data['columns'],
            referencedTable: $data['referenced_table'],
            referencedColumns: $data['referenced_columns'],
            onUpdate: $data['on_update'] ?? 'RESTRICT',
            onDelete: $data['on_delete'] ?? 'RESTRICT',
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Check if foreign key uses CASCADE on delete
     */
    public function cascadesOnDelete(): bool
    {
        return mb_strtoupper($this->onDelete) === 'CASCADE';
    }

    /**
     * Check if foreign key uses CASCADE on update
     */
    public function cascadesOnUpdate(): bool
    {
        return mb_strtoupper($this->onUpdate) === 'CASCADE';
    }

    /**
     * Check if foreign key is composite (multiple columns)
     */
    public function isComposite(): bool
    {
        return count($this->columns) > 1;
    }

    /**
     * Get column mapping as string
     */
    public function getColumnMapping(): string
    {
        $mappings = [];
        foreach ($this->columns as $index => $column) {
            $refColumn = $this->referencedColumns[$index] ?? '?';
            $mappings[] = "{$column} -> {$this->referencedTable}.{$refColumn}";
        }

        return implode(', ', $mappings);
    }

    /**
     * Convert to array for serialization
     *
     * @return array{name: string, table: string, columns: array<string>, referenced_table: string, referenced_columns: array<string>, on_update: 'CASCADE'|'SET NULL'|'RESTRICT'|'NO ACTION', on_delete: 'CASCADE'|'SET NULL'|'RESTRICT'|'NO ACTION', metadata: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'table' => $this->table,
            'columns' => $this->columns,
            'referenced_table' => $this->referencedTable,
            'referenced_columns' => $this->referencedColumns,
            'on_update' => $this->onUpdate,
            'on_delete' => $this->onDelete,
            'metadata' => $this->metadata,
        ];
    }
}
