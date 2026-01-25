<?php

declare(strict_types=1);

use App\Data\ColumnSchema;
use App\Data\DatabaseSchema;
use App\Data\TableSchema;

it('can be instantiated with required parameters', function (): void {
    $schema = new DatabaseSchema(
        databaseName: 'test_db',
        databaseType: 'mysql',
        tables: collect([]),
    );

    expect($schema->databaseName)->toBe('test_db')
        ->and($schema->databaseType)->toBe('mysql')
        ->and($schema->tables)->toBeEmpty()
        ->and($schema->metadata)->toBe([]);
});

it('can be instantiated with metadata', function (): void {
    $schema = new DatabaseSchema(
        databaseName: 'production',
        databaseType: 'pgsql',
        tables: collect([]),
        metadata: ['version' => '15.0', 'charset' => 'utf8'],
    );

    expect($schema->metadata)->toBe(['version' => '15.0', 'charset' => 'utf8']);
});

it('can be instantiated with tables', function (): void {
    $tables = collect([
        new TableSchema('users', collect([]), collect([]), collect([]), collect([])),
        new TableSchema('posts', collect([]), collect([]), collect([]), collect([])),
    ]);

    $schema = new DatabaseSchema(
        databaseName: 'app_db',
        databaseType: 'mysql',
        tables: $tables,
    );

    expect($schema->tables)->toHaveCount(2);
});

it('can get table by name', function (): void {
    $usersTable = new TableSchema('users', collect([]), collect([]), collect([]), collect([]));
    $postsTable = new TableSchema('posts', collect([]), collect([]), collect([]), collect([]));

    $schema = new DatabaseSchema(
        databaseName: 'app_db',
        databaseType: 'mysql',
        tables: collect([$usersTable, $postsTable]),
    );

    $table = $schema->getTable('users');

    expect($table)->not->toBeNull()
        ->and($table->name)->toBe('users');
});

it('returns null for non-existent table', function (): void {
    $schema = new DatabaseSchema(
        databaseName: 'app_db',
        databaseType: 'mysql',
        tables: collect([]),
    );

    expect($schema->getTable('nonexistent'))->toBeNull();
});

it('can check if table exists', function (): void {
    $usersTable = new TableSchema('users', collect([]), collect([]), collect([]), collect([]));

    $schema = new DatabaseSchema(
        databaseName: 'app_db',
        databaseType: 'mysql',
        tables: collect([$usersTable]),
    );

    expect($schema->hasTable('users'))->toBeTrue()
        ->and($schema->hasTable('posts'))->toBeFalse();
});

it('can get table names', function (): void {
    $tables = collect([
        new TableSchema('users', collect([]), collect([]), collect([]), collect([])),
        new TableSchema('posts', collect([]), collect([]), collect([]), collect([])),
        new TableSchema('comments', collect([]), collect([]), collect([]), collect([])),
    ]);

    $schema = new DatabaseSchema(
        databaseName: 'app_db',
        databaseType: 'mysql',
        tables: $tables,
    );

    $names = $schema->getTableNames();

    expect($names->all())->toBe(['users', 'posts', 'comments']);
});

it('can get table count', function (): void {
    $tables = collect([
        new TableSchema('users', collect([]), collect([]), collect([]), collect([])),
        new TableSchema('posts', collect([]), collect([]), collect([]), collect([])),
    ]);

    $schema = new DatabaseSchema(
        databaseName: 'app_db',
        databaseType: 'mysql',
        tables: $tables,
    );

    expect($schema->getTableCount())->toBe(2);
});

it('returns zero table count for empty schema', function (): void {
    $schema = new DatabaseSchema(
        databaseName: 'empty_db',
        databaseType: 'sqlite',
        tables: collect([]),
    );

    expect($schema->getTableCount())->toBe(0);
});

it('can be created from array', function (): void {
    $data = [
        'database_name' => 'test_db',
        'database_type' => 'mysql',
        'tables' => [
            [
                'name' => 'users',
                'columns' => [
                    ['name' => 'id', 'type' => 'int', 'nullable' => false, 'default' => null],
                ],
                'indexes' => [],
                'foreign_keys' => [],
                'constraints' => [],
            ],
        ],
        'metadata' => ['version' => '8.0'],
    ];

    $schema = DatabaseSchema::fromArray($data);

    expect($schema->databaseName)->toBe('test_db')
        ->and($schema->databaseType)->toBe('mysql')
        ->and($schema->tables)->toHaveCount(1)
        ->and($schema->metadata)->toBe(['version' => '8.0']);
});

it('can be converted to array', function (): void {
    $tables = collect([
        new TableSchema(
            'users',
            collect([new ColumnSchema('id', 'int', false, null)]),
            collect([]),
            collect([]),
            collect([])
        ),
    ]);

    $schema = new DatabaseSchema(
        databaseName: 'test_db',
        databaseType: 'pgsql',
        tables: $tables,
        metadata: ['charset' => 'utf8'],
    );

    $array = $schema->toArray();

    expect($array)->toBeArray()
        ->and($array['database_name'])->toBe('test_db')
        ->and($array['database_type'])->toBe('pgsql')
        ->and($array['tables'])->toHaveCount(1)
        ->and($array['metadata'])->toBe(['charset' => 'utf8']);
});
