<?php

declare(strict_types=1);

namespace App\Data;

/**
 * ColumnSchema DTO
 *
 * Represents a single database column including its type,
 * nullability, default value, and other properties.
 */
final readonly class ColumnSchema
{
    /**
     * @param  string  $name  Column name
     * @param  string  $type  Data type (varchar, int, text, etc.)
     * @param  bool  $nullable  Whether column accepts NULL values
     * @param  mixed  $default  Default value (null if no default)
     * @param  int|null  $length  Length/precision for types that support it
     * @param  int|null  $scale  Scale for decimal types
     * @param  bool  $autoIncrement  Whether column is auto-incrementing
     * @param  bool  $unsigned  Whether numeric column is unsigned
     * @param  string|null  $charset  Character set for string columns
     * @param  string|null  $collation  Collation for string columns
     * @param  string|null  $comment  Column comment
     * @param  array<string, mixed>  $metadata  Additional DB-specific metadata
     */
    public function __construct(
        public string $name,
        public string $type,
        public bool $nullable,
        public mixed $default,
        public ?int $length = null,
        public ?int $scale = null,
        public bool $autoIncrement = false,
        public bool $unsigned = false,
        public ?string $charset = null,
        public ?string $collation = null,
        public ?string $comment = null,
        public array $metadata = []
    ) {}

    /**
     * Create from array
     *
     * @param  array{name: string, type: string, nullable: bool, default: mixed, length: null|int, scale: null|int, auto_increment: null|bool, unsigned: null|bool, charset: null|string, collation: null|string, comment: null|string, metadata: null|array<string, mixed>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            nullable: $data['nullable'],
            default: $data['default'],
            length: $data['length'] ?? null,
            scale: $data['scale'] ?? null,
            autoIncrement: $data['auto_increment'] ?? false,
            unsigned: $data['unsigned'] ?? false,
            charset: $data['charset'] ?? null,
            collation: $data['collation'] ?? null,
            comment: $data['comment'] ?? null,
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Get full type definition (e.g., "VARCHAR(255)", "DECIMAL(10,2)")
     */
    public function getFullType(): string
    {
        $fullType = mb_strtoupper($this->type);

        if ($this->length !== null) {
            if ($this->scale !== null) {
                $fullType .= sprintf('(%d,%d)', $this->length, $this->scale);
            } else {
                $fullType .= sprintf('(%d)', $this->length);
            }
        }

        if ($this->unsigned) {
            $fullType .= ' UNSIGNED';
        }

        return $fullType;
    }

    /**
     * Check if column is numeric
     */
    public function isNumeric(): bool
    {
        return in_array(mb_strtolower($this->type), [
            'int', 'integer', 'bigint', 'smallint', 'tinyint', 'mediumint',
            'decimal', 'numeric', 'float', 'double', 'real',
        ]);
    }

    /**
     * Check if column is string type
     */
    public function isString(): bool
    {
        return in_array(mb_strtolower($this->type), [
            'char', 'varchar', 'text', 'tinytext', 'mediumtext', 'longtext',
            'nchar', 'nvarchar', 'ntext',
        ]);
    }

    /**
     * Check if column is date/time type
     */
    public function isDateTime(): bool
    {
        return in_array(mb_strtolower($this->type), [
            'date', 'datetime', 'timestamp', 'time', 'year',
        ]);
    }

    /**
     * Convert to array for serialization
     *
     * @return array{name: string, type: string, nullable: bool, default: mixed, length: null|int, scale: null|int, auto_increment: bool, unsigned: bool, charset: null|string, collation: null|string, comment: null|string, metadata: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'nullable' => $this->nullable,
            'default' => $this->default,
            'length' => $this->length,
            'scale' => $this->scale,
            'auto_increment' => $this->autoIncrement,
            'unsigned' => $this->unsigned,
            'charset' => $this->charset,
            'collation' => $this->collation,
            'comment' => $this->comment,
            'metadata' => $this->metadata,
        ];
    }
}
