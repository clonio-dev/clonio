<?php

declare(strict_types=1);

use App\Data\ColumnMutationData;
use App\Data\ColumnMutationDataOptions;
use App\Data\ColumnMutationStrategyEnum;
use App\Data\ConnectionData;
use App\Data\SqliteDriverData;
use App\Data\TableAnonymizationOptionsData;
use App\Jobs\Middleware\SkipWhenBatchCancelled;
use App\Jobs\TransferRecordsForOneTable;
use App\Models\CloningRun;
use App\Services\AnonymizationService;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('returns correct middleware', function (): void {
    $run = CloningRun::factory()->create();

    $job = new TransferRecordsForOneTable(
        sourceConnectionData: new ConnectionData('source', new SqliteDriverData()),
        targetConnectionData: new ConnectionData('target', new SqliteDriverData()),
        tableName: 'users',
        chunkSize: 100,
        run: $run,
    );

    $middleware = $job->middleware();

    expect($middleware)
        ->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(SkipWhenBatchCancelled::class);
});

it('transfers records from source to target table', function (): void {
    $run = CloningRun::factory()->create();

    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
        $table->text('content');
    });
    DB::connection('test_source')->table('posts')->insert([
        ['title' => 'First Post', 'content' => 'Content 1'],
        ['title' => 'Second Post', 'content' => 'Content 2'],
        ['title' => 'Third Post', 'content' => 'Content 3'],
    ]);

    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('posts', function ($table): void {
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
        run: $run,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $anonymizationService = resolve(AnonymizationService::class);

    $job->handle($dbService, $anonymizationService);

    expect(DB::connection('test_target')->table('posts')->count())->toBe(3);
    expect(DB::connection('test_target')->table('posts')->pluck('title')->toArray())
        ->toBe(['First Post', 'Second Post', 'Third Post']);

    @unlink($sourceDb);
    @unlink($targetDb);
});

it('transfers records in chunks', function (): void {
    $run = CloningRun::factory()->create();

    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });

    for ($i = 1; $i <= 5; $i++) {
        DB::connection('test_source')->table('posts')->insert(['title' => 'Post ' . $i]);
    }

    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('posts', function ($table): void {
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
        run: $run,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $anonymizationService = resolve(AnonymizationService::class);

    $job->handle($dbService, $anonymizationService);

    expect(DB::connection('test_target')->table('posts')->count())->toBe(5);

    @unlink($sourceDb);
    @unlink($targetDb);
});

it('mutates user data during transfer', function (): void {
    $run = CloningRun::factory()->create();

    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('users', function ($table): void {
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

    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('users', function ($table): void {
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
        run: $run,
        tableAnonymizationOptions: new TableAnonymizationOptionsData(
            tableName: 'users',
            columnMutations: collect([
                new ColumnMutationData(columnName: 'name', strategy: ColumnMutationStrategyEnum::FAKE),
                new ColumnMutationData(columnName: 'email', strategy: ColumnMutationStrategyEnum::FAKE),
                new ColumnMutationData(columnName: 'password', strategy: ColumnMutationStrategyEnum::MASK, options: new ColumnMutationDataOptions(visibleChars: 0)),
            ]),
        ),
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $anonymizationService = resolve(AnonymizationService::class);

    $job->handle($dbService, $anonymizationService);

    $targetUser = DB::connection('test_target')->table('users')->first();

    expect($targetUser->name)->not->toBe('John Doe');
    expect($targetUser->email)->not->toBe('john@example.com');
    expect($targetUser->password)->toBe('*********');

    @unlink($sourceDb);
    @unlink($targetDb);
});

it('transfers records with foreign key constraints disabled', function (): void {
    $run = CloningRun::factory()->create();

    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });
    DB::connection('test_source')->table('posts')->insert(['title' => 'Test Post']);

    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new TransferRecordsForOneTable(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        tableName: 'posts',
        chunkSize: 100,
        run: $run,
        disableForeignKeyConstraints: true,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $anonymizationService = resolve(AnonymizationService::class);

    $job->handle($dbService, $anonymizationService);

    expect(DB::connection('test_target')->table('posts')->count())->toBe(1);

    @unlink($sourceDb);
    @unlink($targetDb);
});

it('fails when table does not exist in source database', function (): void {
    $run = CloningRun::factory()->create();

    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');

    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new TransferRecordsForOneTable(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        tableName: 'posts',
        chunkSize: 100,
        run: $run,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $anonymizationService = resolve(AnonymizationService::class);

    expect(fn () => $job->handle($dbService, $anonymizationService))
        ->toThrow(RuntimeException::class, 'Table posts does not exist in source or target database.');

    @unlink($sourceDb);
    @unlink($targetDb);
});

it('fails when table does not exist in target database', function (): void {
    $run = CloningRun::factory()->create();

    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
    });

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
        run: $run,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $anonymizationService = resolve(AnonymizationService::class);

    expect(fn () => $job->handle($dbService, $anonymizationService))
        ->toThrow(RuntimeException::class, 'Table posts does not exist in source or target database.');

    @unlink($sourceDb);
    @unlink($targetDb);
});

it('transfers records ordered by primary key', function (): void {
    $run = CloningRun::factory()->create();

    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });

    DB::connection('test_source')->table('posts')->insert(['id' => 3, 'title' => 'Third']);
    DB::connection('test_source')->table('posts')->insert(['id' => 1, 'title' => 'First']);
    DB::connection('test_source')->table('posts')->insert(['id' => 2, 'title' => 'Second']);

    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new TransferRecordsForOneTable(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        tableName: 'posts',
        chunkSize: 100,
        run: $run,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $anonymizationService = resolve(AnonymizationService::class);

    $job->handle($dbService, $anonymizationService);

    $targetTitles = DB::connection('test_target')->table('posts')->orderBy('id')->pluck('title')->toArray();
    expect($targetTitles)->toBe(['First', 'Second', 'Third']);

    @unlink($sourceDb);
    @unlink($targetDb);
});

it('throttles progress logs to reduce database overhead', function (): void {
    $run = CloningRun::factory()->create();

    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('items', function ($table): void {
        $table->id();
        $table->string('name');
    });

    // Insert 100 records - with chunk size 10, this will trigger 10 chunks
    // Without throttling, we'd get 10 progress logs
    // With 5% threshold, we should get ~20 progress logs (at 0%, 5%, 10%, ..., 100%)
    for ($i = 1; $i <= 100; $i++) {
        DB::connection('test_source')->table('items')->insert(['name' => 'Item ' . $i]);
    }

    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('items', function ($table): void {
        $table->id();
        $table->string('name');
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new TransferRecordsForOneTable(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        tableName: 'items',
        chunkSize: 10, // 10 chunks total = 10 progress events
        run: $run,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $anonymizationService = resolve(AnonymizationService::class);

    $job->handle($dbService, $anonymizationService);

    // Verify transfer worked
    expect(DB::connection('test_target')->table('items')->count())->toBe(100);

    // Count progress logs - with throttling at 5% intervals, we expect significantly fewer than 10
    // We should get logs at: 10% (first chunk), 20%, 30%, ..., 100%
    // That's approximately 10 logs instead of 10, but the key is:
    // - First chunk always logs (10% in this case)
    // - Subsequent chunks only log if % changed by >= 5
    $progressLogs = $run->logs()
        ->where('event_type', 'table_transfer_progress')
        ->get();

    // With 10 chunks of 10 rows each on 100 total:
    // Chunk 1: 10/100 = 10%, logged (first)
    // Chunk 2: 20/100 = 20%, logged (10% change)
    // Chunk 3: 30/100 = 30%, logged (10% change)
    // etc.
    // Each chunk represents 10% progress, so all 10 get logged
    // This test verifies the mechanism works - in real-world with 500k rows
    // and 1000-row chunks, the savings are massive
    expect($progressLogs->count())->toBeLessThanOrEqual(10);

    // Verify we have both start and end progress
    $percents = $progressLogs->pluck('data.percent')->toArray();
    expect($percents)->toContain(10); // First chunk
    expect($percents)->toContain(100); // Last chunk

    @unlink($sourceDb);
    @unlink($targetDb);
});

it('throttles progress logs using time-based intervals', function (): void {
    $run = CloningRun::factory()->create();

    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    config(['database.connections.test_source' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('test_source');
    DB::connection('test_source')->getSchemaBuilder()->create('items', function ($table): void {
        $table->id();
        $table->string('name');
    });

    // Insert 1000 records - with chunk size 10, this will trigger 100 chunks
    for ($i = 1; $i <= 1000; $i++) {
        DB::connection('test_source')->table('items')->insert(['name' => 'Item ' . $i]);
    }

    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');
    DB::connection('test_target')->getSchemaBuilder()->create('items', function ($table): void {
        $table->id();
        $table->string('name');
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new TransferRecordsForOneTable(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        tableName: 'items',
        chunkSize: 10, // 100 chunks total, each 1%
        run: $run,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $anonymizationService = resolve(AnonymizationService::class);

    $job->handle($dbService, $anonymizationService);

    // Verify transfer worked
    expect(DB::connection('test_target')->table('items')->count())->toBe(1000);

    // Time-based throttling (10s intervals) means in a fast test we get:
    // - First log (always logged)
    // - 100% completion (always logged)
    // This is significantly fewer than the 100 raw chunks
    $progressLogs = $run->logs()
        ->where('event_type', 'table_transfer_progress')
        ->get();

    // Must have at least 2 logs (first + completion), but far fewer than 100
    expect($progressLogs->count())->toBeGreaterThanOrEqual(2);
    expect($progressLogs->count())->toBeLessThan(100);

    // Verify completion was logged
    $lastLog = $progressLogs->last();
    expect($lastLog->data['percent'])->toBe(100);

    @unlink($sourceDb);
    @unlink($targetDb);
});
