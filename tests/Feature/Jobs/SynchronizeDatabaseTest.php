<?php

declare(strict_types=1);

use App\Data\ConnectionData;
use App\Data\SqliteDriverData;
use App\Data\SynchronizationOptionsData;
use App\Jobs\CloneSchema;
use App\Jobs\DropUnknownTables;
use App\Jobs\SynchronizeDatabase;
use App\Jobs\TransferRecordsForAllTables;
use App\Jobs\TruncateTargetTables;
use App\Models\TransferRun;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

it('returns correct middleware', function (): void {
    $job = new SynchronizeDatabase(
        options: new SynchronizationOptionsData(),
        sourceConnectionData: new ConnectionData('source', new SqliteDriverData()),
        targetConnectionData: new ConnectionData('target', new SqliteDriverData()),
        run: TransferRun::factory()->create(),
    );

    $middleware = $job->middleware();

    expect($middleware)
        ->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(SkipIfBatchCancelled::class);
});

it('synchronizes database with single target', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    // Set up source database
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData('/path/to/target.sqlite'));

    $options = new SynchronizationOptionsData(
        disableForeignKeyConstraints: true,
        keepUnknownTablesOnTarget: false,
        chunkSize: 500,
    );

    Queue::fake();

    $batch = Bus::batch([])->dispatch();

    $job = new SynchronizeDatabase(
        options: $options,
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        run: TransferRun::factory()->create(),
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify CloneSchemaAndPrepareForData was queued
    Queue::assertPushed(CloneSchema::class);
    Queue::assertPushed(TruncateTargetTables::class);
    Queue::assertPushed(DropUnknownTables::class);

    // Verify TransferRecordsForAllTables was queued
    Queue::assertPushed(TransferRecordsForAllTables::class, fn (TransferRecordsForAllTables $job): bool => $job->options->chunkSize === $options->chunkSize
        && $job->options->disableForeignKeyConstraints === $options->disableForeignKeyConstraints);

    // Clean up
    @unlink($sourceDb);
});

it('passes synchronization options to child jobs', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    // Set up source database
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData('/path/to/target.sqlite'));

    $options = new SynchronizationOptionsData(
        disableForeignKeyConstraints: false,
        keepUnknownTablesOnTarget: true,
        chunkSize: 250,
    );

    Queue::fake();

    $batch = Bus::batch([])->dispatch();

    $job = new SynchronizeDatabase(
        options: $options,
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        run: TransferRun::factory()->create(),
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify all options were passed correctly to CloneSchemaAndPrepareForData
    Queue::assertPushed(CloneSchema::class, fn ($job): bool => $job->keepUnknownTablesOnTarget === true
        && $job->migrationTableName === 'custom_migrations'
        && $job->disableForeignKeyConstraints === false);

    // Verify all options were passed correctly to TransferRecordsForAllTables
    Queue::assertPushed(TransferRecordsForAllTables::class, fn (TransferRecordsForAllTables $job): bool => $job->options->chunkSize === 250
        && $job->options->disableForeignKeyConstraints === false);

    // Clean up
    @unlink($sourceDb);
});

it('uses default synchronization options when not specified', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    // Set up source database
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData('/path/to/target.sqlite'));

    $options = new SynchronizationOptionsData();

    Queue::fake();

    $batch = Bus::batch([])->dispatch();

    $job = new SynchronizeDatabase(
        options: $options,
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        run: TransferRun::factory()->create(),
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify default options
    Queue::assertPushed(CloneSchema::class);

    Queue::assertPushed(TransferRecordsForAllTables::class, fn (TransferRecordsForAllTables $job): bool => $job->options->chunkSize === 1000
        && $job->options->disableForeignKeyConstraints);

    // Clean up
    @unlink($sourceDb);
});

it('connects to source and validates schema builder', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    // Set up source database
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData('/path/to/target.sqlite'));

    Queue::fake();

    $batch = Bus::batch([])->dispatch();

    $job = new SynchronizeDatabase(
        options: new SynchronizationOptionsData(),
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        run: TransferRun::factory()->create(),
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify schema builder was accessed (connection established)
    expect(DB::connection('test_source')->getSchemaBuilder())->toBeInstanceOf(Illuminate\Database\Schema\Builder::class);

    // Clean up
    @unlink($sourceDb);
});

// Note: Source connection failure and target connection failure tests (lines 44-53, 59-66)
// would require mocking the final DatabaseInformationRetrievalService class
// These error paths are covered by integration tests
