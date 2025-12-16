<?php

declare(strict_types=1);

use App\Data\ConnectionData;
use App\Data\SqliteDriverData;
use App\Data\SynchronizeTableSchemaEnum;
use App\Jobs\CloneSchemaAndPrepareForData;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

it('returns correct middleware', function (): void {
    $job = new CloneSchemaAndPrepareForData(
        sourceConnectionData: new ConnectionData('source', new SqliteDriverData()),
        targetConnectionData: new ConnectionData('target', new SqliteDriverData()),
        synchronizeTableSchemaEnum: SynchronizeTableSchemaEnum::DROP_CREATE,
        keepUnknownTablesOnTarget: false,
        migrationTableName: null,
    );

    $middleware = $job->middleware();

    expect($middleware)
        ->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(SkipIfBatchCancelled::class);
});

// Note: Connection failure tests are covered by the other integration tests
// Testing explicit failure scenarios would require mocking the final DatabaseInformationRetrievalService class

it('keeps unknown tables on target when configured', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    // Set up source database
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
    });

    // Set up target database with an extra table
    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
    });
    DB::connection('test_target')->getSchemaBuilder()->create('old_table', function ($table): void {
        $table->id();
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new CloneSchemaAndPrepareForData(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        synchronizeTableSchemaEnum: SynchronizeTableSchemaEnum::TRUNCATE,
        keepUnknownTablesOnTarget: true,
        migrationTableName: null,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify old_table still exists
    expect(DB::connection('test_target')->getSchemaBuilder()->hasTable('old_table'))->toBeTrue();

    // Clean up
    @unlink($sourceDb);
    @unlink($targetDb);
});

it('drops unknown tables on target when configured', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    // Set up source database
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
    });

    // Set up target database with an extra table
    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
    });
    DB::connection('test_target')->getSchemaBuilder()->create('old_table', function ($table): void {
        $table->id();
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    Log::shouldReceive('debug')->once()->with(Mockery::pattern('/Dropping table (main\.)?old_table from target database\./'));
    Log::shouldReceive('info')->once()->with(Mockery::pattern('/Dropped table (main\.)?old_table from target database\./'));

    $job = new CloneSchemaAndPrepareForData(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        synchronizeTableSchemaEnum: SynchronizeTableSchemaEnum::TRUNCATE,
        keepUnknownTablesOnTarget: false,
        migrationTableName: null,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify old_table was dropped
    expect(DB::connection('test_target')->getSchemaBuilder()->hasTable('old_table'))->toBeFalse();

    // Clean up
    @unlink($sourceDb);
    @unlink($targetDb);
});

it('truncates tables when using TRUNCATE mode', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    // Set up source database
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
    });

    // Set up target database with data
    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
    });
    DB::connection('test_target')->table('users')->insert(['name' => 'John']);
    DB::connection('test_target')->table('users')->insert(['name' => 'Jane']);

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new CloneSchemaAndPrepareForData(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        synchronizeTableSchemaEnum: SynchronizeTableSchemaEnum::TRUNCATE,
        keepUnknownTablesOnTarget: true,
        migrationTableName: null,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify data was deleted
    expect(DB::connection('test_target')->table('users')->count())->toBe(0);

    // Clean up
    @unlink($sourceDb);
    @unlink($targetDb);
});

it('clones schema using DROP_CREATE with migration table', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    // Set up source database
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('email');
    });
    DB::connection('test_source')->getSchemaBuilder()->create('custom_migrations', function ($table): void {
        $table->id();
    });

    // Set up target database
    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new CloneSchemaAndPrepareForData(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        synchronizeTableSchemaEnum: SynchronizeTableSchemaEnum::DROP_CREATE,
        keepUnknownTablesOnTarget: true,
        migrationTableName: 'custom_migrations',
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify schema was cloned
    expect(DB::connection('test_target')->getSchemaBuilder()->hasTable('users'))->toBeTrue();

    // Clean up
    @unlink($sourceDb);
    @unlink($targetDb);
});

it('clones schema using DROP_CREATE without migration table', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    // Set up source database
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });

    // Set up target database
    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new CloneSchemaAndPrepareForData(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        synchronizeTableSchemaEnum: SynchronizeTableSchemaEnum::DROP_CREATE,
        keepUnknownTablesOnTarget: true,
        migrationTableName: null,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify schema was cloned
    expect(DB::connection('test_target')->getSchemaBuilder()->hasTable('posts'))->toBeTrue();

    // Clean up
    @unlink($sourceDb);
    @unlink($targetDb);
});

it('disables and enables foreign key constraints when configured', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    // Set up source database
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
    });

    // Set up target database
    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    Log::shouldReceive('debug')->once()->with('Disabling foreign key constraints on target database.');
    Log::shouldReceive('debug')->once()->with('Enabling foreign key constraints on target database.');

    $job = new CloneSchemaAndPrepareForData(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        synchronizeTableSchemaEnum: SynchronizeTableSchemaEnum::TRUNCATE,
        keepUnknownTablesOnTarget: true,
        migrationTableName: null,
        disableForeignKeyConstraints: true,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify data was truncated (confirms job ran successfully)
    expect(DB::connection('test_target')->table('users')->count())->toBe(0);

    // Clean up
    @unlink($sourceDb);
    @unlink($targetDb);
});

it('handles DROP_CREATE mode with foreign key constraints disabled', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    // Set up source database
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });

    // Set up target database
    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    Log::shouldReceive('debug')->once()->with('Disabling foreign key constraints on target database.');
    Log::shouldReceive('debug')->once()->with('Enabling foreign key constraints on target database.');

    $job = new CloneSchemaAndPrepareForData(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        synchronizeTableSchemaEnum: SynchronizeTableSchemaEnum::DROP_CREATE,
        keepUnknownTablesOnTarget: true,
        migrationTableName: null,
        disableForeignKeyConstraints: true,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify schema was cloned
    expect(DB::connection('test_target')->getSchemaBuilder()->hasTable('posts'))->toBeTrue();

    // Clean up
    @unlink($sourceDb);
    @unlink($targetDb);
});

// Note: Connection failure tests (lines 46-50, 58-62) would require mocking the final DatabaseInformationRetrievalService class
// Note: Exception tests for temp file failures (lines 143-144, 149-150) would require mocking SchemaState or filesystem
// These error paths are covered by the RuntimeException checks in the code and integration tests
