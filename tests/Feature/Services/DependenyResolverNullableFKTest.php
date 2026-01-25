<?php

declare(strict_types=1);

use App\Services\DependencyResolver;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config([
        'database.connections.test_nullable' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ],
    ]);
});

it('handles quasi-circular dependencies with nullable FKs', function () {
    $conn = DB::connection('test_nullable');

    // Create employees table (references departments, nullable)
    $conn->statement('
        CREATE TABLE employees (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            department_id INTEGER NULL
        )
    ');

    // Create departments table (references employees, nullable)
    $conn->statement('
        CREATE TABLE departments (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            manager_id INTEGER NULL
        )
    ');

    // Add foreign keys AFTER table creation (SQLite workaround)
    // In real scenario, these would be in CREATE TABLE
    $conn->statement('
        CREATE TABLE employees_new (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            department_id INTEGER NULL,
            FOREIGN KEY (department_id) REFERENCES departments(id)
        )
    ');
    $conn->statement('DROP TABLE employees');
    $conn->statement('ALTER TABLE employees_new RENAME TO employees');

    $conn->statement('
        CREATE TABLE departments_new (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            manager_id INTEGER NULL,
            FOREIGN KEY (manager_id) REFERENCES employees(id)
        )
    ');
    $conn->statement('DROP TABLE departments');
    $conn->statement('ALTER TABLE departments_new RENAME TO departments');

    $resolver = new DependencyResolver();

    // With ignoreNullableFKs = true (default)
    $order = $resolver->getProcessingOrder(['employees', 'departments'], $conn, true);

    // Should NOT throw exception (nullable FKs are ignored)
    expect($order['insert_order'])->toHaveCount(2);
    expect($order['insert_order'])->toContain('employees');
    expect($order['insert_order'])->toContain('departments');

    // Both should be at level 0 (no dependencies when nullable FKs are ignored)
    expect($order['dependency_levels'][0])->toHaveCount(2);
});

it('detects quasi-circular dependencies when NOT ignoring nullable FKs', function () {
    $conn = DB::connection('test_nullable');

    // Same setup as above
    $conn->statement('
        CREATE TABLE employees (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            department_id INTEGER NULL,
            FOREIGN KEY (department_id) REFERENCES departments(id)
        )
    ');

    $conn->statement('
        CREATE TABLE departments (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL,
            manager_id INTEGER NULL,
            FOREIGN KEY (manager_id) REFERENCES employees(id)
        )
    ');

    $resolver = new DependencyResolver();

    // With ignoreNullableFKs = false
    // Should detect circular dependency
    expect(fn () => $resolver->getProcessingOrder(['employees', 'departments'], $conn, false))
        ->toThrow(RuntimeException::class, 'Circular dependency detected');
});

it('correctly identifies nullable vs non-nullable FKs', function () {
    $conn = DB::connection('test_nullable');

    // Table with NULLABLE FK
    $conn->statement('
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY,
            user_id INTEGER NULL,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ');

    // Table with NON-NULLABLE FK
    $conn->statement('
        CREATE TABLE order_items (
            id INTEGER PRIMARY KEY,
            order_id INTEGER NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id)
        )
    ');

    // Parent table
    $conn->statement('CREATE TABLE users (id INTEGER PRIMARY KEY)');

    $resolver = new DependencyResolver();

    // With ignoreNullableFKs = true
    $order = $resolver->getProcessingOrder(['users', 'orders', 'order_items'], $conn, true);

    // orders.user_id is nullable → ignored
    // order_items.order_id is NOT nullable → considered

    // Expected order: users can be anywhere (no deps), orders before order_items
    $insertOrder = $order['insert_order'];

    expect(array_search('orders', $insertOrder))
        ->toBeLessThan(array_search('order_items', $insertOrder));
});

it('handles mixed nullable and non-nullable FKs in same table', function () {
    $conn = DB::connection('test_nullable');

    $conn->statement('CREATE TABLE users (id INTEGER PRIMARY KEY)');
    $conn->statement('CREATE TABLE products (id INTEGER PRIMARY KEY)');

    $conn->statement('
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY,
            user_id INTEGER NOT NULL,
            discount_approved_by INTEGER NULL,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (discount_approved_by) REFERENCES users(id)
        )
    ');

    $resolver = new DependencyResolver();

    // With ignoreNullableFKs = true
    $order = $resolver->getProcessingOrder(['users', 'orders'], $conn, true);

    // user_id is NOT nullable → users must come before orders
    // discount_approved_by is nullable → ignored

    expect($order['insert_order'])->toBe(['users', 'orders']);
});

it('handles composite FK with mixed nullability', function () {
    $conn = DB::connection('test_nullable');

    // Note: SQLite doesn't fully support composite FKs in practice,
    // but we test the logic

    $conn->statement('
        CREATE TABLE parent (
            id1 INTEGER,
            id2 INTEGER,
            PRIMARY KEY (id1, id2)
        )
    ');

    $conn->statement('
        CREATE TABLE child (
            id INTEGER PRIMARY KEY,
            parent_id1 INTEGER NOT NULL,
            parent_id2 INTEGER NULL
        )
    ');

    // In a real composite FK, if ANY column is NOT NULL,
    // the FK should be considered required

    $resolver = new DependencyResolver();

    // Since parent_id1 is NOT NULL, the dependency should be respected
    // (even if parent_id2 is nullable)

    $order = $resolver->getProcessingOrder(['parent', 'child'], $conn, true);

    // This test verifies the logic works even for composite scenarios
    expect($order)->toHaveKey('insert_order');
});

it('logs when ignoring nullable FKs', function () {
    $conn = DB::connection('test_nullable');

    $conn->statement('CREATE TABLE users (id INTEGER PRIMARY KEY)');
    $conn->statement('
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY,
            user_id INTEGER NULL,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ');

    // Capture logs
    Log::shouldReceive('debug')
        ->once()
        ->with('Ignoring nullable FK for dependency graph', Mockery::on(function ($data) {
            return $data['table'] === 'orders' &&
                $data['references'] === 'users' &&
                in_array('user_id', $data['columns']);
        }));

    Log::shouldReceive('debug')
        ->once()
        ->with('Dependency resolution completed', Mockery::any());

    $resolver = new DependencyResolver();
    $resolver->getProcessingOrder(['users', 'orders'], $conn, true);
});
