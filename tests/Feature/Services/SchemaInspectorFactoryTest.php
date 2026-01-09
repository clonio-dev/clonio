<?php

declare(strict_types=1);

use App\Services\SchemaInspector\SchemaInspectorFactory;
use App\Services\SchemaReplicator;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    // Setup two in-memory SQLite databases
    config(['database.connections.source_test' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]]);

    config(['database.connections.target_test' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]]);
});

it('inspects complete database schema', function (): void {
    $conn = DB::connection('source_test');

    // Create test table
    $conn->statement('
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            age INTEGER DEFAULT 0,
            created_at DATETIME
        )
    ');

    $conn->statement('
        CREATE UNIQUE INDEX idx_email ON users (email)
    ');

    $inspector = SchemaInspectorFactory::create($conn);
    $schema = $inspector->getDatabaseSchema($conn);

    expect($schema->databaseType)->toBe('sqlite')
        ->and($schema->getTableNames()->contains('users'))->toBeTrue();

    $usersTable = $schema->getTable('users');

    expect($usersTable)->not->toBeNull()
        ->and($usersTable->getColumnNames()->count())->toBe(5)
        ->and($usersTable->hasColumn('email'))->toBeTrue();

    $emailColumn = $usersTable->getColumn('email');
    expect($emailColumn->type)->toBe('varchar')
        ->and($emailColumn->nullable)->toBeFalse()
        ->and($emailColumn->length)->toBe(255);
});

it('replicates table structure from source to target', function (): void {
    $source = DB::connection('source_test');
    $target = DB::connection('target_test');

    // Create source table with multiple columns
    $source->statement('
        CREATE TABLE products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sku VARCHAR(50) NOT NULL,
            name VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) DEFAULT 0.00,
            stock INTEGER DEFAULT 0,
            active INTEGER DEFAULT 1
        )
    ');

    $source->statement('
        CREATE UNIQUE INDEX idx_sku ON products (sku)
    ');

    // Replicate to target
    $replicator = new SchemaReplicator();
    $replicator->replicateTable($source, $target, 'products');

    // Verify table exists in target
    $targetInspector = SchemaInspectorFactory::create($target);

    expect($targetInspector->tableExists($target, 'products'))->toBeTrue();

    $targetTable = $targetInspector->getTableSchema($target, 'products');

    expect($targetTable->getColumnNames()->count())->toBe(6)
        ->and($targetTable->hasColumn('sku'))->toBeTrue()
        ->and($targetTable->hasColumn('price'))->toBeTrue();

    $priceColumn = $targetTable->getColumn('price');
    expect($priceColumn->type)->toBe('decimal')
        ->and($priceColumn->length)->toBe(10)
        ->and($priceColumn->scale)->toBe(2);
});

it('detects schema differences correctly', function (): void {
    $source = DB::connection('source_test');
    $target = DB::connection('target_test');

    // Create table in source with 4 columns
    $source->statement('
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY,
            customer_id INTEGER NOT NULL,
            total DECIMAL(10,2),
            status VARCHAR(50)
        )
    ');

    // Create table in target with only 2 columns (missing 'total' and 'status')
    $target->statement('
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY,
            customer_id INTEGER NOT NULL
        )
    ');

    $replicator = new SchemaReplicator();
    $diff = $replicator->getSchemaDiff($source, $target);

    expect($diff['table_diffs'])->toHaveKey('orders');

    $ordersDiff = $diff['table_diffs']['orders'];

    expect($ordersDiff['missing_columns'])->toContain('total')
        ->and($ordersDiff['missing_columns'])->toContain('status')
        ->and(count($ordersDiff['missing_columns']))->toBe(2);
});

it('handles foreign keys correctly', function (): void {
    $source = DB::connection('source_test');

    // Create parent table
    $source->statement('
        CREATE TABLE customers (
            id INTEGER PRIMARY KEY,
            name VARCHAR(255)
        )
    ');

    // Create child table with foreign key
    $source->statement('
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY,
            customer_id INTEGER NOT NULL,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        )
    ');

    $inspector = SchemaInspectorFactory::create($source);
    $ordersTable = $inspector->getTableSchema($source, 'orders');

    expect($ordersTable->foreignKeys->count())->toBeGreaterThan(0);

    $fk = $ordersTable->foreignKeys->first();

    expect($fk->referencedTable)->toBe('customers')
        ->and($fk->columns)->toContain('customer_id')
        ->and($fk->referencedColumns)->toContain('id')
        ->and($fk->onDelete)->toBe('CASCADE');
});

it('replicates entire database with multiple tables', function (): void {
    $source = DB::connection('source_test');
    $target = DB::connection('target_test');

    // Create multiple tables in source
    $source->statement('
        CREATE TABLE users (
            id INTEGER PRIMARY KEY,
            name VARCHAR(255)
        )
    ');

    $source->statement('
        CREATE TABLE posts (
            id INTEGER PRIMARY KEY,
            user_id INTEGER,
            title VARCHAR(255),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ');

    $source->statement('
        CREATE TABLE comments (
            id INTEGER PRIMARY KEY,
            post_id INTEGER,
            content TEXT,
            FOREIGN KEY (post_id) REFERENCES posts(id)
        )
    ');

    // Replicate entire database
    $replicator = new SchemaReplicator();
    $replicator->replicateDatabase($source, $target);

    // Verify all tables exist in target
    $targetInspector = SchemaInspectorFactory::create($target);

    expect($targetInspector->tableExists($target, 'users'))->toBeTrue()
        ->and($targetInspector->tableExists($target, 'posts'))->toBeTrue()
        ->and($targetInspector->tableExists($target, 'comments'))->toBeTrue();
});

it('updates existing table with new columns', function (): void {
    $source = DB::connection('source_test');
    $target = DB::connection('target_test');

    // Create initial table in both
    $source->statement('
        CREATE TABLE products (
            id INTEGER PRIMARY KEY,
            name VARCHAR(255)
        )
    ');

    $target->statement('
        CREATE TABLE products (
            id INTEGER PRIMARY KEY,
            name VARCHAR(255)
        )
    ');

    // Add new column to source
    $source->statement('ALTER TABLE products ADD COLUMN price DECIMAL(10,2)');

    // Replicate (should add the missing column to target)
    $replicator = new SchemaReplicator();
    $replicator->replicateTable($source, $target, 'products');

    // Verify new column exists in target
    $targetInspector = SchemaInspectorFactory::create($target);
    $targetTable = $targetInspector->getTableSchema($target, 'products');

    expect($targetTable->hasColumn('price'))->toBeTrue();
});
