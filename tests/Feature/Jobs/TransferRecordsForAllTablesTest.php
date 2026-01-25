<?php

declare(strict_types=1);

use App\Data\ConnectionData;
use App\Data\SqliteDriverData;
use App\Data\SynchronizationOptionsData;
use App\Jobs\Middleware\SkipWhenBatchCancelled;
use App\Jobs\TransferRecordsForAllTables;
use App\Jobs\TransferRecordsForOneTable;
use App\Models\CloningRun;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('returns correct middleware', function (): void {
    $run = CloningRun::factory()->create();

    $job = new TransferRecordsForAllTables(
        sourceConnectionData: new ConnectionData('source', new SqliteDriverData()),
        targetConnectionData: new ConnectionData('target', new SqliteDriverData()),
        options: new SynchronizationOptionsData(chunkSize: 100),
        run: $run,
    );

    $middleware = $job->middleware();

    expect($middleware)
        ->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(SkipWhenBatchCancelled::class);
});

it('transfers records from all tables', function (): void {
    $run = CloningRun::factory()->create();

    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

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
        options: new SynchronizationOptionsData(chunkSize: 100),
        run: $run,
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    Queue::assertPushed(TransferRecordsForOneTable::class, 2);

    @unlink($sourceDb);
});

it('transfers all tables including migrations', function (): void {
    $run = CloningRun::factory()->create();

    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

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
        options: new SynchronizationOptionsData(chunkSize: 100),
        run: $run,
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Both tables should be transferred
    Queue::assertPushed(TransferRecordsForOneTable::class, 2);

    @unlink($sourceDb);
});

it('skips tables with no records', function (): void {
    $run = CloningRun::factory()->create();

    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

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
        options: new SynchronizationOptionsData(chunkSize: 100),
        run: $run,
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    Queue::assertPushed(TransferRecordsForOneTable::class, 1);

    @unlink($sourceDb);
});

it('passes foreign key constraints flag to child jobs', function (): void {
    $run = CloningRun::factory()->create();

    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

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
        options: new SynchronizationOptionsData(chunkSize: 100),
        run: $run,
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    Queue::assertPushed(TransferRecordsForOneTable::class, fn ($job): bool => $job->disableForeignKeyConstraints === true);

    @unlink($sourceDb);
});

it('passes chunk size to child jobs', function (): void {
    $run = CloningRun::factory()->create();

    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

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
        options: new SynchronizationOptionsData(chunkSize: 250),
        run: $run,
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    Queue::assertPushed(TransferRecordsForOneTable::class, fn (TransferRecordsForOneTable $job): bool => $job->chunkSize === 250);

    @unlink($sourceDb);
});

it('handles multiple tables with mixed empty and non-empty', function (): void {
    $run = CloningRun::factory()->create();

    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

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
        options: new SynchronizationOptionsData(chunkSize: 100),
        run: $run,
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    Queue::assertPushed(TransferRecordsForOneTable::class, 2);

    @unlink($sourceDb);
});
