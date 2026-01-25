<?php

declare(strict_types=1);

use App\Services\DependencyResolver;

beforeEach(function (): void {
    // Setup in-memory SQLite for testing
    config(['database.connections.test_deps' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]]);
});

it('resolves simple dependency chain', function (): void {
    $conn = Illuminate\Support\Facades\DB::connection('test_deps');

    // Create tables with FK chain: users -> orders -> order_items
    $conn->statement('CREATE TABLE users (id INTEGER PRIMARY KEY)');
    $conn->statement('
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY,
            user_id INTEGER,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ');
    $conn->statement('
        CREATE TABLE order_items (
            id INTEGER PRIMARY KEY,
            order_id INTEGER,
            FOREIGN KEY (order_id) REFERENCES orders(id)
        )
    ');

    $resolver = new DependencyResolver();
    $order = $resolver->getProcessingOrder(['users', 'orders', 'order_items'], $conn);

    // Assert insert order (parents first)
    expect($order['insert_order'])->toBe(['users', 'orders', 'order_items']);

    // Assert delete order (children first)
    expect($order['delete_order'])->toBe(['order_items', 'orders', 'users']);
});

it('handles multiple parents correctly', function (): void {
    $conn = Illuminate\Support\Facades\DB::connection('test_deps');

    // Create schema: users + products (no deps) -> orders -> order_items
    $conn->statement('CREATE TABLE users (id INTEGER PRIMARY KEY)');
    $conn->statement('CREATE TABLE products (id INTEGER PRIMARY KEY)');
    $conn->statement('
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY,
            user_id INTEGER,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ');
    $conn->statement('
        CREATE TABLE order_items (
            id INTEGER PRIMARY KEY,
            order_id INTEGER,
            product_id INTEGER,
            FOREIGN KEY (order_id) REFERENCES orders(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )
    ');

    $resolver = new DependencyResolver();
    $order = $resolver->getProcessingOrder(
        ['users', 'products', 'orders', 'order_items'],
        $conn
    );

    $insertOrder = $order['insert_order'];

    // Both users and products must come before orders
    expect(array_search('users', $insertOrder))->toBeLessThan(array_search('orders', $insertOrder));
    expect(array_search('products', $insertOrder))->toBeLessThan(array_search('orders', $insertOrder));

    // orders must come before order_items
    expect(array_search('orders', $insertOrder))->toBeLessThan(array_search('order_items', $insertOrder));

    // order_items should be last (depends on both orders and products)
    expect($insertOrder[count($insertOrder) - 1])->toBe('order_items');
});

it('detects circular dependencies', function (): void {
    // Create manual dependency graph with cycle
    $resolver = new DependencyResolver();

    $dependencies = [
        'a' => ['b'],
        'b' => ['c'],
        'c' => ['a'],  // Cycle!
    ];

    expect(fn (): array => $resolver->topologicalSort($dependencies))
        ->toThrow(RuntimeException::class, 'Circular dependency detected');
});

it('handles tables with no dependencies', function (): void {
    $conn = Illuminate\Support\Facades\DB::connection('test_deps');

    // Create independent tables
    $conn->statement('CREATE TABLE categories (id INTEGER PRIMARY KEY)');
    $conn->statement('CREATE TABLE tags (id INTEGER PRIMARY KEY)');
    $conn->statement('CREATE TABLE settings (id INTEGER PRIMARY KEY)');

    $resolver = new DependencyResolver();
    $order = $resolver->getProcessingOrder(['categories', 'tags', 'settings'], $conn);

    // All should be at level 0
    expect($order['dependency_levels'][0])->toHaveCount(3);

    // Insert and delete order don't matter (can be any order)
    expect($order['insert_order'])->toHaveCount(3);
    expect($order['delete_order'])->toHaveCount(3);
});

it('calculates dependency levels correctly', function (): void {
    $conn = Illuminate\Support\Facades\DB::connection('test_deps');

    $conn->statement('CREATE TABLE users (id INTEGER PRIMARY KEY)');
    $conn->statement('CREATE TABLE products (id INTEGER PRIMARY KEY)');
    $conn->statement('
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY,
            user_id INTEGER,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ');
    $conn->statement('
        CREATE TABLE order_items (
            id INTEGER PRIMARY KEY,
            order_id INTEGER,
            product_id INTEGER,
            FOREIGN KEY (order_id) REFERENCES orders(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        )
    ');

    $resolver = new DependencyResolver();
    $order = $resolver->getProcessingOrder(
        ['users', 'products', 'orders', 'order_items'],
        $conn
    );

    $levels = $order['dependency_levels'];

    // Level 0: users + products (no dependencies)
    expect($levels[0])->toContain('users');
    expect($levels[0])->toContain('products');

    // Level 1: orders (depends on users)
    expect($levels[1])->toContain('orders');

    // Level 2: order_items (depends on orders + products)
    expect($levels[2])->toContain('order_items');
});

it('handles self-referencing tables', function (): void {
    $conn = Illuminate\Support\Facades\DB::connection('test_deps');

    // Create self-referencing table (employees with manager_id)
    $conn->statement('
        CREATE TABLE employees (
            id INTEGER PRIMARY KEY,
            manager_id INTEGER,
            FOREIGN KEY (manager_id) REFERENCES employees(id)
        )
    ');
    $conn->statement('CREATE TABLE departments (id INTEGER PRIMARY KEY)');

    $resolver = new DependencyResolver();
    $order = $resolver->getProcessingOrder(['employees', 'departments'], $conn);

    // Self-referencing table should still be processable
    expect($order['insert_order'])->toContain('employees');
    expect($order['insert_order'])->toContain('departments');
});

it('ignores foreign keys to tables not in the list', function (): void {
    $conn = Illuminate\Support\Facades\DB::connection('test_deps');

    // Create tables where some FKs reference tables not in our list
    $conn->statement('CREATE TABLE external_table (id INTEGER PRIMARY KEY)');
    $conn->statement('CREATE TABLE our_table (id INTEGER PRIMARY KEY)');
    $conn->statement('
        CREATE TABLE mixed_table (
            id INTEGER PRIMARY KEY,
            our_id INTEGER,
            external_id INTEGER,
            FOREIGN KEY (our_id) REFERENCES our_table(id),
            FOREIGN KEY (external_id) REFERENCES external_table(id)
        )
    ');

    $resolver = new DependencyResolver();

    // Only process our_table and mixed_table (not external_table)
    $order = $resolver->getProcessingOrder(['our_table', 'mixed_table'], $conn);

    // Should work without error
    expect($order['insert_order'])->toBe(['our_table', 'mixed_table']);

    // mixed_table should depend only on our_table
    $deps = $order['dependency_graph']['mixed_table'];
    expect($deps)->toContain('our_table');
    expect($deps)->not->toContain('external_table');
});

it('formats dependency analysis for display', function (): void {
    $conn = Illuminate\Support\Facades\DB::connection('test_deps');

    $conn->statement('CREATE TABLE users (id INTEGER PRIMARY KEY)');
    $conn->statement('
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY,
            user_id INTEGER,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ');

    $resolver = new DependencyResolver();
    $order = $resolver->getProcessingOrder(['users', 'orders'], $conn);

    $formatted = $resolver->formatDependencyAnalysis($order);

    expect($formatted)->toContain('DEPENDENCY ANALYSIS');
    expect($formatted)->toContain('Level 0');
    expect($formatted)->toContain('Level 1');
    expect($formatted)->toContain('INSERT ORDER');
    expect($formatted)->toContain('DELETE ORDER');
    expect($formatted)->toContain('users');
    expect($formatted)->toContain('orders');
});

it('handles complex multi-level dependencies', function (): void {
    $conn = Illuminate\Support\Facades\DB::connection('test_deps');

    // Create 4-level deep dependency chain
    $conn->statement('CREATE TABLE countries (id INTEGER PRIMARY KEY)');
    $conn->statement('
        CREATE TABLE cities (
            id INTEGER PRIMARY KEY,
            country_id INTEGER,
            FOREIGN KEY (country_id) REFERENCES countries(id)
        )
    ');
    $conn->statement('
        CREATE TABLE users (
            id INTEGER PRIMARY KEY,
            city_id INTEGER,
            FOREIGN KEY (city_id) REFERENCES cities(id)
        )
    ');
    $conn->statement('
        CREATE TABLE orders (
            id INTEGER PRIMARY KEY,
            user_id INTEGER,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ');

    $resolver = new DependencyResolver();
    $order = $resolver->getProcessingOrder(
        ['countries', 'cities', 'users', 'orders'],
        $conn
    );

    // Assert correct order
    expect($order['insert_order'])->toBe(['countries', 'cities', 'users', 'orders']);
    expect($order['delete_order'])->toBe(['orders', 'users', 'cities', 'countries']);

    // Assert levels
    expect($order['dependency_levels'][0])->toBe(['countries']);
    expect($order['dependency_levels'][1])->toBe(['cities']);
    expect($order['dependency_levels'][2])->toBe(['users']);
    expect($order['dependency_levels'][3])->toBe(['orders']);
});

it('can resolve dependencies correct', function (): void {
    $dependencies = [
        'no_deps' => [],
        'one_dep' => ['source'],
        'source' => ['second_dep'],
        'second_dep' => [],
    ];

    $resolver = new DependencyResolver();
    $order = $resolver->topologicalSort($dependencies);
    expect($order)->toBe(['no_deps', 'second_dep', 'source', 'one_dep']);
});

it('correctly resolves the exact scenario from issue', function (): void {
    // Test the exact case that was reported as broken
    $dependencies = [
        'no_deps' => [],
        'one_dep' => ['source'],
        'source' => ['second_dep'],
        'second_dep' => [],
    ];

    $resolver = new DependencyResolver();
    $result = $resolver->topologicalSort($dependencies);

    // Expected order: no_deps, second_dep, source, one_dep
    // (Level 0 tables can be in any order among themselves)

    // Assert the critical ordering constraints:
    // 1. second_dep MUST come before source
    $secondDepIndex = array_search('second_dep', $result);
    $sourceIndex = array_search('source', $result);
    expect($secondDepIndex)->toBeLessThan($sourceIndex);

    // 2. source MUST come before one_dep
    $oneDepIndex = array_search('one_dep', $result);
    expect($sourceIndex)->toBeLessThan($oneDepIndex);

    // 3. no_deps can be anywhere but is typically first (level 0)
    expect($result)->toContain('no_deps');

    // The exact expected order (one valid solution):
    // Note: no_deps and second_dep can be in either order (both level 0)
    $validOrders = [
        ['no_deps', 'second_dep', 'source', 'one_dep'],
        ['second_dep', 'no_deps', 'source', 'one_dep'],
    ];

    expect($result)->toBeIn($validOrders);
});

it('handles complex multi-level chain correctly', function (): void {
    $dependencies = [
        'level_0a' => [],
        'level_0b' => [],
        'level_1' => ['level_0a'],
        'level_2' => ['level_1'],
        'level_3' => ['level_2', 'level_0b'],
    ];

    $resolver = new DependencyResolver();
    $result = $resolver->topologicalSort($dependencies);

    // Critical constraints:
    $level0aIndex = array_search('level_0a', $result);
    $level0bIndex = array_search('level_0b', $result);
    $level1Index = array_search('level_1', $result);
    $level2Index = array_search('level_2', $result);
    $level3Index = array_search('level_3', $result);

    // level_0a must come before level_1
    expect($level0aIndex)->toBeLessThan($level1Index);

    // level_1 must come before level_2
    expect($level1Index)->toBeLessThan($level2Index);

    // level_2 must come before level_3
    expect($level2Index)->toBeLessThan($level3Index);

    // level_0b must come before level_3
    expect($level0bIndex)->toBeLessThan($level3Index);

    // Expected: level_3 should be LAST
    expect($result[count($result) - 1])->toBe('level_3');
});

it('handles diamond dependency correctly', function (): void {
    /*
     *       A
     *      / \
     *     B   C
     *      \ /
     *       D
     */
    $dependencies = [
        'A' => [],
        'B' => ['A'],
        'C' => ['A'],
        'D' => ['B', 'C'],
    ];

    $resolver = new DependencyResolver();
    $result = $resolver->topologicalSort($dependencies);

    $aIndex = array_search('A', $result);
    $bIndex = array_search('B', $result);
    $cIndex = array_search('C', $result);
    $dIndex = array_search('D', $result);

    // A must come before B and C
    expect($aIndex)->toBeLessThan($bIndex);
    expect($aIndex)->toBeLessThan($cIndex);

    // B and C must come before D
    expect($bIndex)->toBeLessThan($dIndex);
    expect($cIndex)->toBeLessThan($dIndex);

    // A should be first
    expect($result[0])->toBe('A');

    // D should be last
    expect($result[count($result) - 1])->toBe('D');
});

it('verifies INSERT order matches schema creation requirements', function (): void {
    // Real-world scenario: users -> orders -> order_items
    $dependencies = [
        'users' => [],
        'products' => [],
        'orders' => ['users'],
        'order_items' => ['orders', 'products'],
    ];

    $resolver = new DependencyResolver();
    $result = $resolver->topologicalSort($dependencies);

    // Users and products must come first (can be in any order)
    $usersIndex = array_search('users', $result);
    $productsIndex = array_search('products', $result);
    $ordersIndex = array_search('orders', $result);
    $orderItemsIndex = array_search('order_items', $result);

    // users must come before orders
    expect($usersIndex)->toBeLessThan($ordersIndex);

    // products must come before order_items
    expect($productsIndex)->toBeLessThan($orderItemsIndex);

    // orders must come before order_items
    expect($ordersIndex)->toBeLessThan($orderItemsIndex);

    // order_items should be LAST (most dependent)
    expect($result[count($result) - 1])->toBe('order_items');
});
