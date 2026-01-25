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
        DB::connection('test_source')->table('posts')->insert(['title' => "Post {$i}"]);
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
