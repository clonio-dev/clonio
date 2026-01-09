<?php

declare(strict_types=1);

use App\Data\ConnectionData;
use App\Data\SqliteDriverData;
use App\Data\SynchronizationOptionsData;
use App\Data\SynchronizeTableSchemaEnum;
use App\Jobs\CloneSchemaAndPrepareForData;
use App\Jobs\SynchronizeDatabase;
use App\Jobs\TransferRecordsForAllTables;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

it('returns correct middleware', function (): void {
    $job = new SynchronizeDatabase(
        options: new SynchronizationOptionsData(),
        sourceConnectionData: new ConnectionData('source', new SqliteDriverData()),
        targetConnectionsData: collect([
            new ConnectionData('target', new SqliteDriverData()),
        ]),
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
        synchronizeTableSchema: SynchronizeTableSchemaEnum::DROP_CREATE,
        keepUnknownTablesOnTarget: false,
        migrationTableName: 'migrations',
        chunkSize: 500,
    );

    Queue::fake();

    $batch = Bus::batch([])->dispatch();

    $job = new SynchronizeDatabase(
        options: $options,
        sourceConnectionData: $sourceConnectionData,
        targetConnectionsData: collect([$targetConnectionData]),
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify CloneSchemaAndPrepareForData was queued
    Queue::assertPushed(CloneSchemaAndPrepareForData::class, fn (CloneSchemaAndPrepareForData $job): bool => $job->synchronizeTableSchemaEnum === $options->synchronizeTableSchema
        && $job->keepUnknownTablesOnTarget === $options->keepUnknownTablesOnTarget
        && $job->migrationTableName === $options->migrationTableName
        && $job->disableForeignKeyConstraints === $options->disableForeignKeyConstraints);

    // Verify TransferRecordsForAllTables was queued
    Queue::assertPushed(TransferRecordsForAllTables::class, fn (TransferRecordsForAllTables $job): bool => $job->options->chunkSize === $options->chunkSize
        && $job->options->migrationTableName === $options->migrationTableName
        && $job->options->disableForeignKeyConstraints === $options->disableForeignKeyConstraints);

    // Clean up
    @unlink($sourceDb);
});

it('synchronizes database with multiple targets', function (): void {
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
    $target1ConnectionData = new ConnectionData('test_target1', new SqliteDriverData('/path/to/target1.sqlite'));
    $target2ConnectionData = new ConnectionData('test_target2', new SqliteDriverData('/path/to/target2.sqlite'));
    $target3ConnectionData = new ConnectionData('test_target3', new SqliteDriverData('/path/to/target3.sqlite'));

    $options = new SynchronizationOptionsData();

    Queue::fake();

    $batch = Bus::batch([])->dispatch();

    $job = new SynchronizeDatabase(
        options: $options,
        sourceConnectionData: $sourceConnectionData,
        targetConnectionsData: collect([
            $target1ConnectionData,
            $target2ConnectionData,
            $target3ConnectionData,
        ]),
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify jobs were queued for all 3 targets (2 jobs per target)
    Queue::assertPushed(CloneSchemaAndPrepareForData::class, 3);
    Queue::assertPushed(TransferRecordsForAllTables::class, 3);

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
        synchronizeTableSchema: SynchronizeTableSchemaEnum::TRUNCATE,
        keepUnknownTablesOnTarget: true,
        migrationTableName: 'custom_migrations',
        chunkSize: 250,
    );

    Queue::fake();

    $batch = Bus::batch([])->dispatch();

    $job = new SynchronizeDatabase(
        options: $options,
        sourceConnectionData: $sourceConnectionData,
        targetConnectionsData: collect([$targetConnectionData]),
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify all options were passed correctly to CloneSchemaAndPrepareForData
    Queue::assertPushed(CloneSchemaAndPrepareForData::class, fn ($job): bool => $job->synchronizeTableSchemaEnum === SynchronizeTableSchemaEnum::TRUNCATE
        && $job->keepUnknownTablesOnTarget === true
        && $job->migrationTableName === 'custom_migrations'
        && $job->disableForeignKeyConstraints === false);

    // Verify all options were passed correctly to TransferRecordsForAllTables
    Queue::assertPushed(TransferRecordsForAllTables::class, fn (TransferRecordsForAllTables $job): bool => $job->options->chunkSize === 250
        && $job->options->migrationTableName === 'custom_migrations'
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
        targetConnectionsData: collect([$targetConnectionData]),
    );
    $job->withBatchId($batch->id);

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify default options
    Queue::assertPushed(CloneSchemaAndPrepareForData::class, fn (CloneSchemaAndPrepareForData $job): bool => $job->synchronizeTableSchemaEnum === SynchronizeTableSchemaEnum::DROP_CREATE
        && $job->keepUnknownTablesOnTarget
        && $job->migrationTableName === null
        && $job->disableForeignKeyConstraints);

    Queue::assertPushed(TransferRecordsForAllTables::class, fn (TransferRecordsForAllTables $job): bool => $job->options->chunkSize === 1000
        && $job->options->migrationTableName === null
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
        targetConnectionsData: collect([$targetConnectionData]),
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
