<?php

declare(strict_types=1);

namespace App\Data;

/**
 * IndexSchema DTO
 *
 * Represents a database index including primary keys, unique indexes,
 * and regular indexes.
 */
final readonly class IndexSchema
{
    /**
     * @param  string  $name  Index name
     * @param  string  $type  Index type: 'primary', 'unique', 'index', 'fulltext', 'spatial'
     * @param  array<string>  $columns  Columns included in the index
     * @param  array<string, mixed>  $metadata  Additional DB-specific metadata
     */
    public function __construct(
        public string $name,
        public string $type,
        public array $columns,
        public array $metadata = []
    ) {}

    /**
     * Create from array
     *
     * @param  array{name: string, type: string, columns: array<string>, metadata: null|array<string, mixed>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            columns: $data['columns'],
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Check if this is a primary key
     */
    public function isPrimary(): bool
    {
        return $this->type === 'primary';
    }

    /**
     * Check if this is a unique index
     */
    public function isUnique(): bool
    {
        return in_array($this->type, ['primary', 'unique']);
    }

    /**
     * Check if index is on multiple columns
     */
    public function isComposite(): bool
    {
        return count($this->columns) > 1;
    }

    /**
     * Get column list as comma-separated string
     */
    public function getColumnList(): string
    {
        return implode(', ', $this->columns);
    }

    /**
     * Convert to array for serialization
     *
     * @return array{name: string, type: string, columns: array<string>, metadata: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'columns' => $this->columns,
            'metadata' => $this->metadata,
        ];
    }
}
