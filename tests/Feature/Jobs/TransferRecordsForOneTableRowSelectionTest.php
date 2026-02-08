<?php

declare(strict_types=1);

use App\Data\ConnectionData;
use App\Data\RowSelectionStrategyEnum;
use App\Data\SqliteDriverData;
use App\Data\TableAnonymizationOptionsData;
use App\Data\TableRowSelectionData;
use App\Jobs\TransferRecordsForOneTable;
use App\Models\CloningRun;
use App\Services\AnonymizationService;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createTestDatabases(string $prefix = 'rowsel'): array
{
    $sourceDb = tempnam(sys_get_temp_dir(), "source_{$prefix}_");
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), "target_{$prefix}_");
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    config(["database.connections.test_source_{$prefix}" => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge("test_source_{$prefix}");

    config(["database.connections.test_target_{$prefix}" => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge("test_target_{$prefix}");

    return [
        'sourceDb' => $sourceDb,
        'targetDb' => $targetDb,
        'sourceConn' => "test_source_{$prefix}",
        'targetConn' => "test_target_{$prefix}",
        'sourceConnectionData' => new ConnectionData("test_source_{$prefix}", new SqliteDriverData($sourceDb)),
        'targetConnectionData' => new ConnectionData("test_target_{$prefix}", new SqliteDriverData($targetDb)),
    ];
}

function cleanupTestDatabases(string $sourceDb, string $targetDb): void
{
    @unlink($sourceDb);
    @unlink($targetDb);
}

it('transfers first X rows ordered ascending', function (): void {
    $dbs = createTestDatabases('first_x');
    $run = CloningRun::factory()->create();

    DB::connection($dbs['sourceConn'])->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });

    for ($i = 1; $i <= 10; $i++) {
        DB::connection($dbs['sourceConn'])->table('posts')->insert(['title' => "Post {$i}"]);
    }

    DB::connection($dbs['targetConn'])->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });

    $job = new TransferRecordsForOneTable(
        sourceConnectionData: $dbs['sourceConnectionData'],
        targetConnectionData: $dbs['targetConnectionData'],
        tableName: 'posts',
        chunkSize: 100,
        run: $run,
        tableAnonymizationOptions: new TableAnonymizationOptionsData(
            tableName: 'posts',
            columnMutations: collect(),
            rowSelection: new TableRowSelectionData(
                strategy: RowSelectionStrategyEnum::FirstX,
                limit: 5,
                sortColumn: 'id',
            ),
        ),
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $anonymizationService = resolve(AnonymizationService::class);

    $job->handle($dbService, $anonymizationService);

    $targetPosts = DB::connection($dbs['targetConn'])->table('posts')->orderBy('id')->get();
    expect($targetPosts)->toHaveCount(5);
    expect($targetPosts->pluck('id')->toArray())->toBe([1, 2, 3, 4, 5]);

    cleanupTestDatabases($dbs['sourceDb'], $dbs['targetDb']);
});

it('transfers last X rows ordered descending', function (): void {
    $dbs = createTestDatabases('last_x');
    $run = CloningRun::factory()->create();

    DB::connection($dbs['sourceConn'])->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });

    for ($i = 1; $i <= 10; $i++) {
        DB::connection($dbs['sourceConn'])->table('posts')->insert(['title' => "Post {$i}"]);
    }

    DB::connection($dbs['targetConn'])->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });

    $job = new TransferRecordsForOneTable(
        sourceConnectionData: $dbs['sourceConnectionData'],
        targetConnectionData: $dbs['targetConnectionData'],
        tableName: 'posts',
        chunkSize: 100,
        run: $run,
        tableAnonymizationOptions: new TableAnonymizationOptionsData(
            tableName: 'posts',
            columnMutations: collect(),
            rowSelection: new TableRowSelectionData(
                strategy: RowSelectionStrategyEnum::LastX,
                limit: 3,
                sortColumn: 'id',
            ),
        ),
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $anonymizationService = resolve(AnonymizationService::class);

    $job->handle($dbService, $anonymizationService);

    $targetPosts = DB::connection($dbs['targetConn'])->table('posts')->orderBy('id')->get();
    expect($targetPosts)->toHaveCount(3);
    // Last 3 rows ordered by id DESC → ids 10, 9, 8
    expect($targetPosts->pluck('id')->toArray())->toBe([8, 9, 10]);

    cleanupTestDatabases($dbs['sourceDb'], $dbs['targetDb']);
});

it('transfers all rows when strategy is full table', function (): void {
    $dbs = createTestDatabases('full');
    $run = CloningRun::factory()->create();

    DB::connection($dbs['sourceConn'])->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });

    for ($i = 1; $i <= 5; $i++) {
        DB::connection($dbs['sourceConn'])->table('posts')->insert(['title' => "Post {$i}"]);
    }

    DB::connection($dbs['targetConn'])->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });

    $job = new TransferRecordsForOneTable(
        sourceConnectionData: $dbs['sourceConnectionData'],
        targetConnectionData: $dbs['targetConnectionData'],
        tableName: 'posts',
        chunkSize: 100,
        run: $run,
        tableAnonymizationOptions: new TableAnonymizationOptionsData(
            tableName: 'posts',
            columnMutations: collect(),
            rowSelection: new TableRowSelectionData(
                strategy: RowSelectionStrategyEnum::FullTable,
            ),
        ),
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $anonymizationService = resolve(AnonymizationService::class);

    $job->handle($dbService, $anonymizationService);

    expect(DB::connection($dbs['targetConn'])->table('posts')->count())->toBe(5);

    cleanupTestDatabases($dbs['sourceDb'], $dbs['targetDb']);
});

it('applies FK filters to limit child rows', function (): void {
    $dbs = createTestDatabases('fk');
    $run = CloningRun::factory()->create();

    // Create source tables
    DB::connection($dbs['sourceConn'])->getSchemaBuilder()->create('comments', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('post_id');
        $table->text('body');
    });

    // Insert comments for various post IDs
    DB::connection($dbs['sourceConn'])->table('comments')->insert([
        ['post_id' => 1, 'body' => 'Comment on post 1 - A'],
        ['post_id' => 1, 'body' => 'Comment on post 1 - B'],
        ['post_id' => 2, 'body' => 'Comment on post 2'],
        ['post_id' => 3, 'body' => 'Comment on post 3'],
        ['post_id' => 4, 'body' => 'Comment on post 4'],
    ]);

    // Create target table
    DB::connection($dbs['targetConn'])->getSchemaBuilder()->create('comments', function ($table): void {
        $table->id();
        $table->unsignedBigInteger('post_id');
        $table->text('body');
    });

    // Only allow comments for post_id 1 and 3 (simulating FK filter from row-limited parent)
    $job = new TransferRecordsForOneTable(
        sourceConnectionData: $dbs['sourceConnectionData'],
        targetConnectionData: $dbs['targetConnectionData'],
        tableName: 'comments',
        chunkSize: 100,
        run: $run,
        foreignKeyFilters: [
            ['column' => 'post_id', 'values' => [1, 3]],
        ],
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $anonymizationService = resolve(AnonymizationService::class);

    $job->handle($dbService, $anonymizationService);

    $targetComments = DB::connection($dbs['targetConn'])->table('comments')->get();
    expect($targetComments)->toHaveCount(3);
    expect($targetComments->pluck('post_id')->unique()->sort()->values()->toArray())->toBe([1, 3]);

    cleanupTestDatabases($dbs['sourceDb'], $dbs['targetDb']);
});

it('handles row selection with chunked data correctly', function (): void {
    $dbs = createTestDatabases('chunked');
    $run = CloningRun::factory()->create();

    DB::connection($dbs['sourceConn'])->getSchemaBuilder()->create('items', function ($table): void {
        $table->id();
        $table->string('name');
    });

    for ($i = 1; $i <= 20; $i++) {
        DB::connection($dbs['sourceConn'])->table('items')->insert(['name' => "Item {$i}"]);
    }

    DB::connection($dbs['targetConn'])->getSchemaBuilder()->create('items', function ($table): void {
        $table->id();
        $table->string('name');
    });

    // First 10 rows with chunk size of 3 → should still get exactly 10
    $job = new TransferRecordsForOneTable(
        sourceConnectionData: $dbs['sourceConnectionData'],
        targetConnectionData: $dbs['targetConnectionData'],
        tableName: 'items',
        chunkSize: 3,
        run: $run,
        tableAnonymizationOptions: new TableAnonymizationOptionsData(
            tableName: 'items',
            columnMutations: collect(),
            rowSelection: new TableRowSelectionData(
                strategy: RowSelectionStrategyEnum::FirstX,
                limit: 10,
                sortColumn: 'id',
            ),
        ),
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $anonymizationService = resolve(AnonymizationService::class);

    $job->handle($dbService, $anonymizationService);

    expect(DB::connection($dbs['targetConn'])->table('items')->count())->toBe(10);

    cleanupTestDatabases($dbs['sourceDb'], $dbs['targetDb']);
});
