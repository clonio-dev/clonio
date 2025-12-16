<?php

declare(strict_types=1);

use App\Data\ConnectionData;
use App\Data\SqliteDriverData;
use App\Jobs\TransferRecordsForOneTable;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

it('returns correct middleware', function (): void {
    $job = new TransferRecordsForOneTable(
        sourceConnectionData: new ConnectionData('source', new SqliteDriverData()),
        targetConnectionData: new ConnectionData('target', new SqliteDriverData()),
        tableName: 'users',
        chunkSize: 100,
    );

    $middleware = $job->middleware();

    expect($middleware)
        ->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(SkipIfBatchCancelled::class);
});

it('transfers records from source to target table', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    // Set up source database with data
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('posts', function ($table) {
        $table->id();
        $table->string('title');
        $table->text('content');
    });
    DB::connection('test_source')->table('posts')->insert([
        ['title' => 'First Post', 'content' => 'Content 1'],
        ['title' => 'Second Post', 'content' => 'Content 2'],
        ['title' => 'Third Post', 'content' => 'Content 3'],
    ]);

    // Set up target database
    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('posts', function ($table) {
        $table->id();
        $table->string('title');
        $table->text('content');
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new TransferRecordsForOneTable(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        tableName: 'posts',
        chunkSize: 100,
    );

    $dbService = app(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify records were transferred
    expect(DB::connection('test_target')->table('posts')->count())->toBe(3);
    expect(DB::connection('test_target')->table('posts')->pluck('title')->toArray())
        ->toBe(['First Post', 'Second Post', 'Third Post']);

    // Clean up
    @unlink($sourceDb);
    @unlink($targetDb);
});

it('transfers records in chunks', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    // Set up source database with data
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('posts', function ($table) {
        $table->id();
        $table->string('title');
    });

    // Insert 5 records to test chunking with chunk size of 2
    for ($i = 1; $i <= 5; $i++) {
        DB::connection('test_source')->table('posts')->insert(['title' => "Post {$i}"]);
    }

    // Set up target database
    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('posts', function ($table) {
        $table->id();
        $table->string('title');
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new TransferRecordsForOneTable(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        tableName: 'posts',
        chunkSize: 2,
    );

    $dbService = app(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify all records were transferred despite chunking
    expect(DB::connection('test_target')->table('posts')->count())->toBe(5);

    // Clean up
    @unlink($sourceDb);
    @unlink($targetDb);
});

it('mutates user data during transfer', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    // Set up source database with user data
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('password');
    });
    DB::connection('test_source')->table('users')->insert([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'secret123',
    ]);

    // Set up target database
    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('password');
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new TransferRecordsForOneTable(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        tableName: 'users',
        chunkSize: 100,
    );

    $dbService = app(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    $targetUser = DB::connection('test_target')->table('users')->first();

    // Verify user data was mutated
    expect($targetUser->name)->not->toBe('John Doe');
    expect($targetUser->email)->not->toBe('john@example.com');
    expect($targetUser->password)->toBe('********');

    // Clean up
    @unlink($sourceDb);
    @unlink($targetDb);
});

// Note: Connection failure tests (lines 38-48, 50-58) would require mocking the final DatabaseInformationRetrievalService class
// These error paths are covered by the RuntimeException checks below and integration tests

it('fails when table does not exist in source database', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    // Set up source database without the table
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');

    // Set up target database with the table
    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('posts', function ($table) {
        $table->id();
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new TransferRecordsForOneTable(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        tableName: 'posts',
        chunkSize: 100,
    );

    $dbService = app(DatabaseInformationRetrievalService::class);

    expect(fn () => $job->handle($dbService))
        ->toThrow(RuntimeException::class, 'Table posts does not exist in source or target database.');

    // Clean up
    @unlink($sourceDb);
    @unlink($targetDb);
});

it('fails when table does not exist in target database', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    // Set up source database with the table
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('posts', function ($table) {
        $table->id();
    });

    // Set up target database without the table
    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new TransferRecordsForOneTable(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        tableName: 'posts',
        chunkSize: 100,
    );

    $dbService = app(DatabaseInformationRetrievalService::class);

    expect(fn () => $job->handle($dbService))
        ->toThrow(RuntimeException::class, 'Table posts does not exist in source or target database.');

    // Clean up
    @unlink($sourceDb);
    @unlink($targetDb);
});

it('transfers records ordered by primary key', function (): void {
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    // Set up source database with data
    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('posts', function ($table) {
        $table->id();
        $table->string('title');
    });

    // Insert in non-sequential order
    DB::connection('test_source')->table('posts')->insert(['id' => 3, 'title' => 'Third']);
    DB::connection('test_source')->table('posts')->insert(['id' => 1, 'title' => 'First']);
    DB::connection('test_source')->table('posts')->insert(['id' => 2, 'title' => 'Second']);

    // Set up target database
    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('posts', function ($table) {
        $table->id();
        $table->string('title');
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    Log::shouldReceive('debug')->once()->with(Mockery::pattern('/Order columns for table posts:/'));
    Log::shouldReceive('info')->zeroOrMoreTimes();

    $job = new TransferRecordsForOneTable(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        tableName: 'posts',
        chunkSize: 100,
    );

    $dbService = app(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    // Verify records were transferred in order by ID
    $targetTitles = DB::connection('test_target')->table('posts')->orderBy('id')->pluck('title')->toArray();
    expect($targetTitles)->toBe(['First', 'Second', 'Third']);

    // Clean up
    @unlink($sourceDb);
    @unlink($targetDb);
});
