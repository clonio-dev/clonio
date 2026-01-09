<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ConstraintSchema
{
    /**
     * @param  string  $name  Constraint name
     * @param  string  $type  Constraint type: 'check', 'default', 'unique', 'not_null'
     * @param  string|null  $column  Column name (if column-level constraint)
     * @param  string|null  $expression  Constraint expression/definition
     * @param  array<string, mixed>  $metadata  Additional DB-specific metadata
     */
    public function __construct(
        public string $name,
        public string $type,
        public ?string $column,
        public ?string $expression,
        public array $metadata = []
    ) {}

    /**
     * Create from array
     *
     * @param  array{name: string, type: string, column: null|string, expression: null|string, metadata: null|array<string, mixed>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            column: $data['column'] ?? null,
            expression: $data['expression'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Check if constraint is a CHECK constraint
     */
    public function isCheck(): bool
    {
        return $this->type === 'check';
    }

    /**
     * Check if constraint is column-level
     */
    public function isColumnLevel(): bool
    {
        return $this->column !== null;
    }

    /**
     * Check if constraint is table-level
     */
    public function isTableLevel(): bool
    {
        return $this->column === null;
    }

    /**
     * Convert to array for serialization
     *
     * @return array{name: string, type: string, column: null|string, expression: null|string, metadata: null|array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'column' => $this->column,
            'expression' => $this->expression,
            'metadata' => $this->metadata,
        ];
    }
}
