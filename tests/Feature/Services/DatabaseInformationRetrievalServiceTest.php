<?php

declare(strict_types=1);

use App\Data\ConnectionData;
use App\Data\SqliteDriverData;
use App\Services\DatabaseInformationRetrievalService;
use App\Services\TableInformationRetrievalService;
use Illuminate\Support\Facades\DB;

it('establishes a connection successfully', function (): void {
    $db = tempnam(sys_get_temp_dir(), 'test_');
    @unlink($db);
    $db .= '.sqlite';
    touch($db);

    config(['database.connections.test_db' => [
        'driver' => 'sqlite',
        'database' => $db,
    ]]);
    DB::purge('test_db');

    $connectionData = new ConnectionData('test_db', new SqliteDriverData($db));
    $service = resolve(DatabaseInformationRetrievalService::class);

    $connection = $service->getConnection($connectionData);

    expect($connection)->toBeInstanceOf(Illuminate\Database\ConnectionInterface::class);

    @unlink($db);
});

it('caches connections and reuses them', function (): void {
    $db = tempnam(sys_get_temp_dir(), 'test_');
    @unlink($db);
    $db .= '.sqlite';
    touch($db);

    config(['database.connections.test_db' => [
        'driver' => 'sqlite',
        'database' => $db,
    ]]);
    DB::purge('test_db');

    $connectionData = new ConnectionData('test_db', new SqliteDriverData($db));
    $service = resolve(DatabaseInformationRetrievalService::class);

    $connection1 = $service->getConnection($connectionData);
    $connection2 = $service->getConnection($connectionData);

    // Same instance should be returned (cached)
    expect($connection1)->toBe($connection2);

    @unlink($db);
});

it('establishes connection even with invalid path but fails on actual use', function (): void {
    $connectionData = new ConnectionData('invalid_db', new SqliteDriverData('/invalid/path/to/database.sqlite'));
    $service = resolve(DatabaseInformationRetrievalService::class);

    // Connection is established lazily, so this won't fail
    $connection = $service->getConnection($connectionData);
    expect($connection)->toBeInstanceOf(Illuminate\Database\ConnectionInterface::class);

    // But using it will fail
    expect(fn () => $connection->getSchemaBuilder()->getTableListing())
        ->toThrow(Exception::class);
});

it('returns schema builder for non-existent database but fails on use', function (): void {
    $nonExistentDb = '/tmp/definitely_does_not_exist_' . uniqid() . '.sqlite';

    $connectionData = new ConnectionData('nonexistent_db', new SqliteDriverData($nonExistentDb));
    $service = resolve(DatabaseInformationRetrievalService::class);

    // getSchema returns a builder, but using it will fail
    $schema = $service->getSchema($connectionData);
    expect($schema)->toBeInstanceOf(Illuminate\Database\Schema\Builder::class);

    // Actual operation fails
    expect(fn () => $schema->getTableListing())
        ->toThrow(Exception::class);
});

it('returns schema builder for a connection', function (): void {
    $db = tempnam(sys_get_temp_dir(), 'test_');
    @unlink($db);
    $db .= '.sqlite';
    touch($db);

    config(['database.connections.test_db' => [
        'driver' => 'sqlite',
        'database' => $db,
    ]]);
    DB::purge('test_db');

    $connectionData = new ConnectionData('test_db', new SqliteDriverData($db));
    $service = resolve(DatabaseInformationRetrievalService::class);

    $schema = $service->getSchema($connectionData);

    expect($schema)->toBeInstanceOf(Illuminate\Database\Schema\Builder::class);

    @unlink($db);
});

it('returns table names from database', function (): void {
    $db = tempnam(sys_get_temp_dir(), 'test_');
    @unlink($db);
    $db .= '.sqlite';
    touch($db);

    config(['database.connections.test_db' => [
        'driver' => 'sqlite',
        'database' => $db,
    ]]);
    DB::purge('test_db');

    DB::connection('test_db')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
    });

    DB::connection('test_db')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
    });

    DB::connection('test_db')->getSchemaBuilder()->create('comments', function ($table): void {
        $table->id();
    });

    $connectionData = new ConnectionData('test_db', new SqliteDriverData($db));
    $service = resolve(DatabaseInformationRetrievalService::class);

    $tableNames = $service->getTableNames($connectionData);

    expect($tableNames)->toBeArray()
        ->and($tableNames)->toContain('users')
        ->and($tableNames)->toContain('posts')
        ->and($tableNames)->toContain('comments');

    @unlink($db);
});

it('returns schema qualified table names when requested', function (): void {
    $db = tempnam(sys_get_temp_dir(), 'test_');
    @unlink($db);
    $db .= '.sqlite';
    touch($db);

    config(['database.connections.test_db' => [
        'driver' => 'sqlite',
        'database' => $db,
    ]]);
    DB::purge('test_db');

    DB::connection('test_db')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
    });

    $connectionData = new ConnectionData('test_db', new SqliteDriverData($db));
    $service = resolve(DatabaseInformationRetrievalService::class);

    $tableNames = $service->getTableNames($connectionData, schemaQualified: true);

    expect($tableNames)->toBeArray();
    // SQLite should return schema qualified names like "main.users"
    expect($tableNames[0])->toContain('users');

    @unlink($db);
});

it('returns TableInformationRetrievalService for a specific table', function (): void {
    $db = tempnam(sys_get_temp_dir(), 'test_');
    @unlink($db);
    $db .= '.sqlite';
    touch($db);

    config(['database.connections.test_db' => [
        'driver' => 'sqlite',
        'database' => $db,
    ]]);
    DB::purge('test_db');

    DB::connection('test_db')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
    });

    DB::connection('test_db')->table('users')->insert(['name' => 'John']);
    DB::connection('test_db')->table('users')->insert(['name' => 'Jane']);

    $connectionData = new ConnectionData('test_db', new SqliteDriverData($db));
    $service = resolve(DatabaseInformationRetrievalService::class);

    $tableService = $service->withConnectionForTable($connectionData, 'users');

    expect($tableService)->toBeInstanceOf(TableInformationRetrievalService::class);
    expect($tableService->recordCount())->toBe(2);

    @unlink($db);
});

it('handles multiple different connections', function (): void {
    $db1 = tempnam(sys_get_temp_dir(), 'test1_');
    @unlink($db1);
    $db1 .= '.sqlite';
    touch($db1);

    $db2 = tempnam(sys_get_temp_dir(), 'test2_');
    @unlink($db2);
    $db2 .= '.sqlite';
    touch($db2);

    config(['database.connections.test_db1' => [
        'driver' => 'sqlite',
        'database' => $db1,
    ]]);
    DB::purge('test_db1');

    config(['database.connections.test_db2' => [
        'driver' => 'sqlite',
        'database' => $db2,
    ]]);
    DB::purge('test_db2');

    DB::connection('test_db1')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
    });

    DB::connection('test_db2')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
    });

    $connectionData1 = new ConnectionData('test_db1', new SqliteDriverData($db1));
    $connectionData2 = new ConnectionData('test_db2', new SqliteDriverData($db2));

    $service = resolve(DatabaseInformationRetrievalService::class);

    $connection1 = $service->getConnection($connectionData1);
    $connection2 = $service->getConnection($connectionData2);

    // Different connections should be different instances
    expect($connection1)->not->toBe($connection2);

    $tables1 = $service->getTableNames($connectionData1);
    $tables2 = $service->getTableNames($connectionData2);

    expect($tables1)->toContain('users')
        ->and($tables1)->not->toContain('posts');

    expect($tables2)->toContain('posts')
        ->and($tables2)->not->toContain('users');

    @unlink($db1);
    @unlink($db2);
});

it('returns schema builder for empty path but fails on use', function (): void {
    $connectionData = new ConnectionData('invalid', new SqliteDriverData(''));
    $service = resolve(DatabaseInformationRetrievalService::class);

    // Schema builder is returned
    $schema = $service->getSchema($connectionData);
    expect($schema)->toBeInstanceOf(Illuminate\Database\Schema\Builder::class);

    // But using it will fail
    expect(fn () => $schema->getTableListing())
        ->toThrow(Exception::class);
});

it('returns schema builder for invalid path but fails on use', function (): void {
    $connectionData = new ConnectionData('invalid_schema', new SqliteDriverData('/invalid/path.sqlite'));
    $service = resolve(DatabaseInformationRetrievalService::class);

    $schema = $service->getSchema($connectionData);
    expect($schema)->toBeInstanceOf(Illuminate\Database\Schema\Builder::class);

    // Actual database operation will fail
    expect(fn () => $schema->hasTable('users'))
        ->toThrow(Exception::class);
});

it('fails when getting table names from invalid database', function (): void {
    $connectionData = new ConnectionData('invalid_tables', new SqliteDriverData('/invalid/path.sqlite'));
    $service = resolve(DatabaseInformationRetrievalService::class);

    expect(fn () => $service->getTableNames($connectionData))
        ->toThrow(Exception::class);
});

it('fails when creating table service for invalid database', function (): void {
    $connectionData = new ConnectionData('invalid_table_service', new SqliteDriverData('/invalid/path.sqlite'));
    $service = resolve(DatabaseInformationRetrievalService::class);

    // Connection is established, but withConnectionForTable itself won't fail
    // It will only fail when the TableInformationRetrievalService is actually used
    $tableService = $service->withConnectionForTable($connectionData, 'users');
    expect($tableService)->toBeInstanceOf(TableInformationRetrievalService::class);

    // But using it will fail
    expect(fn () => $tableService->recordCount())
        ->toThrow(Exception::class);
});
