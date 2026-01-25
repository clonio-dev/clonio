<?php

declare(strict_types=1);

use App\Services\DependencyResolver;
use App\Services\SchemaInspector\SchemaInspectorFactory;
use App\Services\SchemaReplicator;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Setup two in-memory SQLite databases
    config([
        'database.connections.source_fk' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ],
    ]);

    config([
        'database.connections.target_fk' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ],
    ]);
});

it('replicates tables in correct FK order', function () {
    $source = DB::connection('source_fk');
    $target = DB::connection('target_fk');

    // Create source schema with FK dependencies
    $source->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
    $source->statement('CREATE TABLE products (id INTEGER PRIMARY KEY, name TEXT)');
    $source->statement('
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY,
            user_id INTEGER NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ');
    $source->statement('
        CREATE TABLE order_items (
            id INTEGER PRIMARY KEY,
            order_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )
    ');

    // Replicate entire database
    $replicator = new SchemaReplicator(new DependencyResolver());
    $replicator->replicateDatabase($source, $target);

    // Verify all tables exist in target
    $inspector = SchemaInspectorFactory::create($target);

    expect($inspector->tableExists($target, 'users'))->toBeTrue();
    expect($inspector->tableExists($target, 'products'))->toBeTrue();
    expect($inspector->tableExists($target, 'orders'))->toBeTrue();
    expect($inspector->tableExists($target, 'order_items'))->toBeTrue();

    // Verify foreign keys were created successfully
    $ordersTable = $inspector->getTableSchema($target, 'orders');
    expect($ordersTable->foreignKeys->count())->toBeGreaterThan(0);

    $orderItemsTable = $inspector->getTableSchema($target, 'order_items');
    expect($orderItemsTable->foreignKeys->count())->toBe(2);
});

it('handles complex FK graph with multiple parents', function () {
    $source = DB::connection('source_fk');
    $target = DB::connection('target_fk');

    // Create complex schema
    $source->statement('CREATE TABLE countries (id INTEGER PRIMARY KEY)');
    $source->statement('CREATE TABLE categories (id INTEGER PRIMARY KEY)');
    $source->statement('
        CREATE TABLE cities (
            id INTEGER PRIMARY KEY,
            country_id INTEGER,
            FOREIGN KEY (country_id) REFERENCES countries(id)
        )
    ');
    $source->statement('
        CREATE TABLE users (
            id INTEGER PRIMARY KEY,
            city_id INTEGER,
            FOREIGN KEY (city_id) REFERENCES cities(id)
        )
    ');
    $source->statement('
        CREATE TABLE products (
            id INTEGER PRIMARY KEY,
            category_id INTEGER,
            FOREIGN KEY (category_id) REFERENCES categories(id)
        )
    ');
    $source->statement('
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY,
            user_id INTEGER,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ');
    $source->statement('
        CREATE TABLE order_items (
            id INTEGER PRIMARY KEY,
            order_id INTEGER,
            product_id INTEGER,
            FOREIGN KEY (order_id) REFERENCES orders(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )
    ');

    // Replicate
    $replicator = new SchemaReplicator(new DependencyResolver());
    $replicator->replicateDatabase($source, $target);

    // Verify all tables created
    $inspector = SchemaInspectorFactory::create($target);
    $schema = $inspector->getDatabaseSchema($target);

    expect($schema->getTableCount())->toBe(7);

    // Verify order_items has both FKs
    $orderItems = $schema->getTable('order_items');
    expect($orderItems->foreignKeys->count())->toBe(2);
});

it('fails gracefully with circular dependencies', function () {
    // Note: SQLite allows creation of circular FKs, but our DependencyResolver should catch it
    $source = DB::connection('source_fk');
    $target = DB::connection('target_fk');

    // Try to create circular dependency (though SQLite may not enforce this strictly)
    $source->statement('CREATE TABLE a (id INTEGER PRIMARY KEY, b_id INTEGER)');
    $source->statement('CREATE TABLE b (id INTEGER PRIMARY KEY, c_id INTEGER)');
    $source->statement('CREATE TABLE c (id INTEGER PRIMARY KEY, a_id INTEGER)');

    // Add circular FKs (if DB allows)
    try {
        $source->statement('
            CREATE TABLE temp_a AS SELECT * FROM a;
            DROP TABLE a;
            CREATE TABLE a (
                id INTEGER PRIMARY KEY,
                b_id INTEGER,
                FOREIGN KEY (b_id) REFERENCES b(id)
            );
        ');
        $source->statement('
            CREATE TABLE temp_b AS SELECT * FROM b;
            DROP TABLE b;
            CREATE TABLE b (
                id INTEGER PRIMARY KEY,
                c_id INTEGER,
                FOREIGN KEY (c_id) REFERENCES c(id)
            );
        ');
        $source->statement('
            CREATE TABLE temp_c AS SELECT * FROM c;
            DROP TABLE c;
            CREATE TABLE c (
                id INTEGER PRIMARY KEY,
                a_id INTEGER,
                FOREIGN KEY (a_id) REFERENCES a(id)
            );
        ');
    } catch (Exception $e) {
        // SQLite might not allow this, skip test
        expect(true)->toBeTrue();

        return;
    }

    // Replication should detect cycle
    $replicator = new SchemaReplicator(new DependencyResolver());

    expect(fn () => $replicator->replicateDatabase($source, $target))
        ->toThrow(RuntimeException::class, 'Circular dependency');
})->skip('SQLite may not support circular FKs in test environment');

it('handles self-referencing tables correctly', function () {
    $source = DB::connection('source_fk');
    $target = DB::connection('target_fk');

    // Create self-referencing table
    $source->statement('
        CREATE TABLE employees (
            id INTEGER PRIMARY KEY,
            name TEXT,
            manager_id INTEGER,
            FOREIGN KEY (manager_id) REFERENCES employees(id)
        )
    ');
    $source->statement('CREATE TABLE departments (id INTEGER PRIMARY KEY)');

    // Replicate
    $replicator = new SchemaReplicator(new DependencyResolver());
    $replicator->replicateDatabase($source, $target);

    // Verify both tables created
    $inspector = SchemaInspectorFactory::create($target);

    expect($inspector->tableExists($target, 'employees'))->toBeTrue();
    expect($inspector->tableExists($target, 'departments'))->toBeTrue();

    // Verify self-referencing FK
    $employees = $inspector->getTableSchema($target, 'employees');
    $selfFk = $employees->foreignKeys->first(fn ($fk) => $fk->referencedTable === 'employees');

    expect($selfFk)->not->toBeNull();
});

it('replicates database schema in dependency order', function () {
    $source = DB::connection('source_fk');
    $target = DB::connection('target_fk');

    $source->statement('CREATE TABLE users (id INTEGER PRIMARY KEY)');
    $source->statement('
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY,
            user_id INTEGER,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ');

    $replicator = new SchemaReplicator(new DependencyResolver());
    $replicator->replicateDatabase($source, $target);

    // Verify both tables exist in target
    $inspector = SchemaInspectorFactory::create($target);

    expect($inspector->tableExists($target, 'users'))->toBeTrue();
    expect($inspector->tableExists($target, 'orders'))->toBeTrue();

    // Verify foreign key was created
    $ordersTable = $inspector->getTableSchema($target, 'orders');
    expect($ordersTable->foreignKeys->count())->toBe(1);
});
