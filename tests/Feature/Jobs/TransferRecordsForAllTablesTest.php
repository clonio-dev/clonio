<?php

declare(strict_types=1);

use App\Data\ConnectionData;
use App\Data\SqliteDriverData;
use App\Data\SynchronizationOptionsData;
use App\Jobs\TransferRecordsForAllTables;
use App\Jobs\TransferRecordsForOneTable;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

it('returns correct middleware', function (): void {
    $job = new TransferRecordsForAllTables(
        sourceConnectionData: new ConnectionData('source', new SqliteDriverData()),
        targetConnectionData: new ConnectionData('target', new SqliteDriverData()),
        options: new SynchronizationOptionsData(
            chunkSize: 100,
        ),
    );

    $middleware = $job->middleware();

    expect($middleware)
        ->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(SkipIfBatchCancelled::class);
});

it('transfers records from all tables', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    // Set up source database with multiple tables
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');

    DB::connection('test_source')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
    });
    DB::connection('test_source')->table('users')->insert(['name' => 'John']);
    DB::connection('test_source')->table('users')->insert(['name' => 'Jane']);

    DB::connection('test_source')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });
    DB::connection('test_source')->table('posts')->insert(['title' => 'Post 1']);

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData('/path/to/target.sqlite'));

    Queue::fake();

    $batch = Bus::batch([])->dispatch();

    $job = new TransferRecordsForAllTables(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        options: new SynchronizationOptionsData(
            chunkSize: 100,
        ),
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify TransferRecordsForOneTable jobs were queued for both tables
    Queue::assertPushed(TransferRecordsForOneTable::class, 2);

    // Clean up
    @unlink($sourceDb);
});

it('skips migration table when specified', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    // Set up source database with multiple tables including migrations
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');

    DB::connection('test_source')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
    });
    DB::connection('test_source')->table('users')->insert(['name' => 'John']);

    DB::connection('test_source')->getSchemaBuilder()->create('migrations', function ($table): void {
        $table->id();
        $table->string('migration');
    });
    DB::connection('test_source')->table('migrations')->insert(['migration' => '2024_01_01_000000_create_users_table']);

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData('/path/to/target.sqlite'));

    Queue::fake();

    $batch = Bus::batch([])->dispatch();

    $job = new TransferRecordsForAllTables(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        options: new SynchronizationOptionsData(
            chunkSize: 100,
        ),
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify only users table job was queued (migrations skipped)
    Queue::assertPushed(TransferRecordsForOneTable::class, 1);

    // Clean up
    @unlink($sourceDb);
});

it('skips tables with no records', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    // Set up source database with empty and non-empty tables
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');

    DB::connection('test_source')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
    });
    DB::connection('test_source')->table('users')->insert(['name' => 'John']);

    DB::connection('test_source')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });
    // posts table is empty

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData('/path/to/target.sqlite'));

    Queue::fake();

    $batch = Bus::batch([])->dispatch();

    $job = new TransferRecordsForAllTables(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        options: new SynchronizationOptionsData(
            chunkSize: 100,
        ),
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify only users table job was queued (posts skipped because empty)
    Queue::assertPushed(TransferRecordsForOneTable::class, 1);

    // Clean up
    @unlink($sourceDb);
});

it('passes foreign key constraints flag to child jobs', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    // Set up source database with a table
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');

    DB::connection('test_source')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
    });
    DB::connection('test_source')->table('users')->insert(['name' => 'John']);

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData('/path/to/target.sqlite'));

    Queue::fake();

    $batch = Bus::batch([])->dispatch();

    $job = new TransferRecordsForAllTables(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        options: new SynchronizationOptionsData(
            chunkSize: 100,
        ),
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify the child job has disableForeignKeyConstraints set to true
    Queue::assertPushed(TransferRecordsForOneTable::class, fn ($job): bool => $job->disableForeignKeyConstraints === true);

    // Clean up
    @unlink($sourceDb);
});

it('passes chunk size to child jobs', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    // Set up source database with a table
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');

    DB::connection('test_source')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
    });
    DB::connection('test_source')->table('users')->insert(['name' => 'John']);

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData('/path/to/target.sqlite'));

    Queue::fake();

    $batch = Bus::batch([])->dispatch();

    $job = new TransferRecordsForAllTables(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        options: new SynchronizationOptionsData(
            chunkSize: 250,
        ),
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify the child job has correct chunk size
    Queue::assertPushed(TransferRecordsForOneTable::class, fn (TransferRecordsForOneTable $job): bool => $job->chunkSize === 250);

    // Clean up
    @unlink($sourceDb);
});

it('handles multiple tables with mixed empty and non-empty', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    // Set up source database with multiple tables, some empty, some with records
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');

    DB::connection('test_source')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
    });
    DB::connection('test_source')->table('users')->insert(['name' => 'John']);
    DB::connection('test_source')->table('users')->insert(['name' => 'Jane']);

    DB::connection('test_source')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });
    // posts is empty

    DB::connection('test_source')->getSchemaBuilder()->create('comments', function ($table): void {
        $table->id();
        $table->text('content');
    });
    DB::connection('test_source')->table('comments')->insert(['content' => 'Nice!']);

    DB::connection('test_source')->getSchemaBuilder()->create('tags', function ($table): void {
        $table->id();
        $table->string('name');
    });
    // tags is empty

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData('/path/to/target.sqlite'));

    Queue::fake();

    $batch = Bus::batch([])->dispatch();

    $job = new TransferRecordsForAllTables(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        options: new SynchronizationOptionsData(
            chunkSize: 100
        ),
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify only non-empty tables (users and comments) got jobs
    Queue::assertPushed(TransferRecordsForOneTable::class, 2);

    // Clean up
    @unlink($sourceDb);
});

// Note: Connection failure test (lines 35-42) would require mocking the final DatabaseInformationRetrievalService class
// This error path is covered by the RuntimeException checks and integration tests
