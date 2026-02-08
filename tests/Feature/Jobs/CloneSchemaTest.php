<?php

declare(strict_types=1);

use App\Data\ConnectionData;
use App\Data\SqliteDriverData;
use App\Jobs\CloneSchema;
use App\Jobs\Middleware\SkipWhenBatchCancelled;
use App\Models\CloningRun;
use App\Services\DatabaseInformationRetrievalService;
use App\Services\SchemaReplicator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('returns correct middleware', function (): void {
    $run = CloningRun::factory()->create();

    $job = new CloneSchema(
        sourceConnectionData: new ConnectionData('source', new SqliteDriverData()),
        targetConnectionData: new ConnectionData('target', new SqliteDriverData()),
        tables: ['users'],
        run: $run,
    );

    $middleware = $job->middleware();

    expect($middleware)
        ->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(SkipWhenBatchCancelled::class);
});

it('clones schema from source to target', function (): void {
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
    });

    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new CloneSchema(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        tables: ['users'],
        run: $run,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $schemaReplicator = resolve(SchemaReplicator::class);

    $job->handle($dbService, $schemaReplicator);

    expect(DB::connection('test_target')->getSchemaBuilder()->hasTable('users'))->toBeTrue();

    @unlink($sourceDb);
    @unlink($targetDb);
});

it('clones multiple tables', function (): void {
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
    });
    DB::connection('test_source')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });

    config(['database.connections.test_target' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('test_target');

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new CloneSchema(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        tables: ['users', 'posts'],
        run: $run,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $schemaReplicator = resolve(SchemaReplicator::class);

    $job->handle($dbService, $schemaReplicator);

    expect(DB::connection('test_target')->getSchemaBuilder()->hasTable('users'))->toBeTrue();
    expect(DB::connection('test_target')->getSchemaBuilder()->hasTable('posts'))->toBeTrue();

    @unlink($sourceDb);
    @unlink($targetDb);
});

it('preserves existing data when replicating schema', function (): void {
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
    });

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

    expect(DB::connection('test_target')->table('users')->count())->toBe(2);

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData($targetDb));

    $job = new CloneSchema(
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        tables: ['users'],
        run: $run,
    );

    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $schemaReplicator = resolve(SchemaReplicator::class);

    $job->handle($dbService, $schemaReplicator);

    // Schema replication preserves existing data - truncation is done by separate job
    expect(DB::connection('test_target')->table('users')->count())->toBe(2);

    @unlink($sourceDb);
    @unlink($targetDb);
});
