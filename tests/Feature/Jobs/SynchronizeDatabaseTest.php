<?php

declare(strict_types=1);

use App\Data\ConnectionData;
use App\Data\SqliteDriverData;
use App\Data\SynchronizationOptionsData;
use App\Jobs\Middleware\SkipWhenBatchCancelled;
use App\Jobs\SynchronizeDatabase;
use App\Models\CloningRun;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('returns correct middleware', function (): void {
    $run = CloningRun::factory()->create();

    $job = new SynchronizeDatabase(
        options: new SynchronizationOptionsData(),
        sourceConnectionData: new ConnectionData('source', new SqliteDriverData()),
        targetConnectionData: new ConnectionData('target', new SqliteDriverData()),
        run: $run,
    );

    $middleware = $job->middleware();

    expect($middleware)
        ->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(SkipWhenBatchCancelled::class);
});

it('handles synchronization request', function (): void {
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
    });

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData($sourceDb));
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData('/path/to/target.sqlite'));

    $options = new SynchronizationOptionsData(chunkSize: 500);

    Bus::fake();

    $batch = Bus::batch([])->dispatch();

    $job = new SynchronizeDatabase(
        options: $options,
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        run: $run,
    );
    $job->withBatchId($batch->id);

    // Job should complete without throwing exceptions
    $dbService = resolve(DatabaseInformationRetrievalService::class);
    $job->handle($dbService);

    @unlink($sourceDb);
})->throwsNoExceptions();

it('stores options for child jobs', function (): void {
    $run = CloningRun::factory()->create();

    $options = new SynchronizationOptionsData(
        chunkSize: 250,
    );

    $job = new SynchronizeDatabase(
        options: $options,
        sourceConnectionData: new ConnectionData('test_source', new SqliteDriverData()),
        targetConnectionData: new ConnectionData('test_target', new SqliteDriverData()),
        run: $run,
    );

    expect($job->options->chunkSize)->toBe(250);
});

it('uses default options when not specified', function (): void {
    $run = CloningRun::factory()->create();

    $options = new SynchronizationOptionsData();

    $job = new SynchronizeDatabase(
        options: $options,
        sourceConnectionData: new ConnectionData('test_source', new SqliteDriverData()),
        targetConnectionData: new ConnectionData('test_target', new SqliteDriverData()),
        run: $run,
    );

    expect($job->options->chunkSize)->toBe(1000);
});

it('stores connection data', function (): void {
    $run = CloningRun::factory()->create();

    $sourceConnectionData = new ConnectionData('test_source', new SqliteDriverData());
    $targetConnectionData = new ConnectionData('test_target', new SqliteDriverData());

    $job = new SynchronizeDatabase(
        options: new SynchronizationOptionsData(),
        sourceConnectionData: $sourceConnectionData,
        targetConnectionData: $targetConnectionData,
        run: $run,
    );

    expect($job->sourceConnectionData->name)->toBe('test_source');
    expect($job->targetConnectionData->name)->toBe('test_target');
});
