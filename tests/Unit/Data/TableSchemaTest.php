<?php

declare(strict_types=1);

use App\Data\ColumnSchema;
use App\Data\ConstraintSchema;
use App\Data\ForeignKeySchema;
use App\Data\IndexSchema;
use App\Data\TableSchema;

it('can be instantiated with required parameters', function (): void {
    $table = new TableSchema(
        name: 'users',
        columns: collect([]),
        indexes: collect([]),
        foreignKeys: collect([]),
        constraints: collect([]),
    );

    expect($table->name)->toBe('users')
        ->and($table->columns)->toBeEmpty()
        ->and($table->indexes)->toBeEmpty()
        ->and($table->foreignKeys)->toBeEmpty()
        ->and($table->constraints)->toBeEmpty()
        ->and($table->metadata)->toBe([])
        ->and($table->metricsData)->toBeNull();
});

it('can be instantiated with columns', function (): void {
    $columns = collect([
        new ColumnSchema('id', 'int', false, null, autoIncrement: true),
        new ColumnSchema('name', 'varchar', false, null, length: 255),
    ]);

    $table = new TableSchema(
        name: 'users',
        columns: $columns,
        indexes: collect([]),
        foreignKeys: collect([]),
        constraints: collect([]),
    );

    expect($table->columns)->toHaveCount(2);
});

it('can get column by name', function (): void {
    $columns = collect([
        new ColumnSchema('id', 'int', false, null),
        new ColumnSchema('email', 'varchar', false, null, length: 255),
    ]);

    $table = new TableSchema(
        name: 'users',
        columns: $columns,
        indexes: collect([]),
        foreignKeys: collect([]),
        constraints: collect([]),
    );

    $column = $table->getColumn('email');

    expect($column)->not->toBeNull()
        ->and($column->name)->toBe('email')
        ->and($column->type)->toBe('varchar');
});

it('returns null for non-existent column', function (): void {
    $table = new TableSchema(
        name: 'users',
        columns: collect([new ColumnSchema('id', 'int', false, null)]),
        indexes: collect([]),
        foreignKeys: collect([]),
        constraints: collect([]),
    );

    expect($table->getColumn('nonexistent'))->toBeNull();
});

it('can check if column exists', function (): void {
    $table = new TableSchema(
        name: 'users',
        columns: collect([new ColumnSchema('id', 'int', false, null)]),
        indexes: collect([]),
        foreignKeys: collect([]),
        constraints: collect([]),
    );

    expect($table->hasColumn('id'))->toBeTrue()
        ->and($table->hasColumn('email'))->toBeFalse();
});

it('can get column names', function (): void {
    $columns = collect([
        new ColumnSchema('id', 'int', false, null),
        new ColumnSchema('name', 'varchar', false, null),
        new ColumnSchema('email', 'varchar', false, null),
    ]);

    $table = new TableSchema(
        name: 'users',
        columns: $columns,
        indexes: collect([]),
        foreignKeys: collect([]),
        constraints: collect([]),
    );

    $names = $table->getColumnNames();

    expect($names->all())->toBe(['id', 'name', 'email']);
});

it('can get primary key', function (): void {
    $indexes = collect([
        new IndexSchema('PRIMARY', 'primary', ['id']),
        new IndexSchema('idx_email', 'unique', ['email']),
    ]);

    $table = new TableSchema(
        name: 'users',
        columns: collect([]),
        indexes: $indexes,
        foreignKeys: collect([]),
        constraints: collect([]),
    );

    $pk = $table->getPrimaryKey();

    expect($pk)->not->toBeNull()
        ->and($pk->name)->toBe('PRIMARY')
        ->and($pk->columns)->toBe(['id']);
});

it('returns null when no primary key exists', function (): void {
    $table = new TableSchema(
        name: 'log_entries',
        columns: collect([]),
        indexes: collect([new IndexSchema('idx_timestamp', 'index', ['timestamp'])]),
        foreignKeys: collect([]),
        constraints: collect([]),
    );

    expect($table->getPrimaryKey())->toBeNull();
});

it('can get foreign keys', function (): void {
    $fks = collect([
        new ForeignKeySchema('fk_user', 'posts', ['user_id'], 'users', ['id']),
        new ForeignKeySchema('fk_category', 'posts', ['category_id'], 'categories', ['id']),
    ]);

    $table = new TableSchema(
        name: 'posts',
        columns: collect([]),
        indexes: collect([]),
        foreignKeys: $fks,
        constraints: collect([]),
    );

    expect($table->getForeignKeys())->toHaveCount(2);
});

it('can get foreign keys referencing specific table', function (): void {
    $fks = collect([
        new ForeignKeySchema('fk_user', 'posts', ['user_id'], 'users', ['id']),
        new ForeignKeySchema('fk_category', 'posts', ['category_id'], 'categories', ['id']),
        new ForeignKeySchema('fk_author', 'posts', ['author_id'], 'users', ['id']),
    ]);

    $table = new TableSchema(
        name: 'posts',
        columns: collect([]),
        indexes: collect([]),
        foreignKeys: $fks,
        constraints: collect([]),
    );

    $userFks = $table->getForeignKeysReferencingTable('users');

    expect($userFks)->toHaveCount(2);
});

it('can be created from array', function (): void {
    $data = [
        'name' => 'posts',
        'columns' => [
            ['name' => 'id', 'type' => 'int', 'nullable' => false, 'default' => null],
            ['name' => 'title', 'type' => 'varchar', 'nullable' => false, 'default' => null, 'length' => 255],
        ],
        'indexes' => [
            ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id']],
        ],
        'foreign_keys' => [
            [
                'name' => 'fk_user',
                'table' => 'posts',
                'columns' => ['user_id'],
                'referenced_table' => 'users',
                'referenced_columns' => ['id'],
            ],
        ],
        'constraints' => [
            ['name' => 'chk_title', 'type' => 'check', 'column' => 'title', 'expression' => 'LENGTH(title) > 0'],
        ],
        'metadata' => ['engine' => 'InnoDB'],
    ];

    $table = TableSchema::fromArray($data);

    expect($table->name)->toBe('posts')
        ->and($table->columns)->toHaveCount(2)
        ->and($table->indexes)->toHaveCount(1)
        ->and($table->foreignKeys)->toHaveCount(1)
        ->and($table->constraints)->toHaveCount(1)
        ->and($table->metadata)->toBe(['engine' => 'InnoDB']);
});

it('can be converted to array', function (): void {
    $table = new TableSchema(
        name: 'users',
        columns: collect([new ColumnSchema('id', 'int', false, null)]),
        indexes: collect([new IndexSchema('PRIMARY', 'primary', ['id'])]),
        foreignKeys: collect([]),
        constraints: collect([]),
        metadata: ['engine' => 'InnoDB'],
    );

    $array = $table->toArray();

    expect($array)->toBeArray()
        ->and($array['name'])->toBe('users')
        ->and($array['columns'])->toHaveCount(1)
        ->and($array['indexes'])->toHaveCount(1)
        ->and($array['foreign_keys'])->toBeEmpty()
        ->and($array['constraints'])->toBeEmpty()
        ->and($array['metadata'])->toBe(['engine' => 'InnoDB']);
});
