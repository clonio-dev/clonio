<?php

declare(strict_types=1);

use App\Services\DependencyResolver;
use App\Services\SchemaReplicator;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    config([
        'database.connections.source_ect' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ],
    ]);

    config([
        'database.connections.target_ect' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ],
    ]);
});

it('only adds missing columns by default without modifying existing column types', function (): void {
    $source = DB::connection('source_ect');
    $target = DB::connection('target_ect');

    // Source: users table with TEXT for name and extra email column
    $source->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)');

    // Target: users table with name as INTEGER (type mismatch) and missing email
    $target->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name INTEGER)');

    $replicator = new SchemaReplicator(new DependencyResolver());

    $events = [];
    $replicator->replicateDatabase($source, $target, ['users'], function (string $table, string $event, string $message) use (&$events): void {
        $events[] = $message;
    });

    // email should be added
    expect($events)->toContain('Missing column added: email');

    // name should NOT be modified (enforceColumnTypes defaults to false)
    $modifiedEvents = array_filter($events, fn (string $e): bool => str_contains($e, 'Modified'));
    expect($modifiedEvents)->toBeEmpty();
});

it('reports no changes when table structure matches and enforceColumnTypes is off', function (): void {
    $source = DB::connection('source_ect');
    $target = DB::connection('target_ect');

    // Same structure on both
    $source->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
    $target->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');

    $replicator = new SchemaReplicator(new DependencyResolver());

    $events = [];
    $replicator->replicateDatabase($source, $target, ['users'], function (string $table, string $event, string $message) use (&$events): void {
        $events[] = $message;
    });

    expect($events)->toContain('No changes necessary');
});

it('attempts column modification when enforceColumnTypes is enabled (catches SQLite limitation)', function (): void {
    $source = DB::connection('source_ect');
    $target = DB::connection('target_ect');

    // Type mismatch
    $source->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
    $target->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name INTEGER)');

    $replicator = new SchemaReplicator(new DependencyResolver());

    // SQLite doesn't support column modification, so this should throw
    expect(fn () => $replicator->replicateDatabase(
        $source,
        $target,
        ['users'],
        null,
        enforceColumnTypesMap: ['users' => true],
    ))->toThrow(RuntimeException::class, 'SQLite does not support modifying columns');
});

it('does not attempt column modification for tables not in enforceColumnTypesMap', function (): void {
    $source = DB::connection('source_ect');
    $target = DB::connection('target_ect');

    // Type mismatch on both tables
    $source->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
    $source->statement('CREATE TABLE posts (id INTEGER PRIMARY KEY, title TEXT)');

    $target->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name INTEGER)');
    $target->statement('CREATE TABLE posts (id INTEGER PRIMARY KEY, title INTEGER)');

    $replicator = new SchemaReplicator(new DependencyResolver());

    // Only users has enforceColumnTypes, but SQLite throws on modify
    // Posts should not throw since enforceColumnTypes is not enabled for it
    // If we only enable for users, we expect an exception
    expect(fn () => $replicator->replicateDatabase(
        $source,
        $target,
        ['users', 'posts'],
        null,
        enforceColumnTypesMap: ['users' => true],
    ))->toThrow(RuntimeException::class);

    // But without enforce for either table, no exception should occur
    $events = [];
    $replicator->replicateDatabase(
        $source,
        $target,
        ['posts'],
        function (string $table, string $event, string $message) use (&$events): void {
            $events[] = ['table' => $table, 'message' => $message];
        },
    );

    // posts should report "No changes necessary" (no missing columns, type diffs ignored)
    $postsMessages = array_filter($events, fn (array $e): bool => $e['table'] === 'posts');
    $postsModified = array_filter($postsMessages, fn (array $e): bool => str_contains((string) $e['message'], 'Modified'));
    expect($postsModified)->toBeEmpty();
});
