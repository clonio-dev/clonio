<?php

declare(strict_types=1);

use App\Data\ConnectionData;
use App\Data\KeyRemappingConfigData;
use App\Data\KeyRemappingStrategyEnum;
use App\Data\KeyRemappingTableData;
use App\Data\SqliteDriverData;
use App\Data\SynchronizationOptionsData;
use App\Jobs\BuildKeyMappingJob;
use App\Jobs\CleanupKeyMappingJob;
use App\Models\CloningRun;
use App\Services\DatabaseInformationRetrievalService;
use App\Services\KeyMappingRepository;
use App\Services\KeyRemappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('maps all PKs into the permanent mappings table', function (): void {
    $run = CloningRun::factory()->create();

    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    config(['database.connections.bim_source' => ['driver' => 'sqlite', 'database' => $sourceDb]]);
    DB::purge('bim_source');
    DB::connection('bim_source')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
    });
    DB::connection('bim_source')->table('users')->insert([
        ['name' => 'Alice'],
        ['name' => 'Bob'],
        ['name' => 'Charlie'],
    ]);

    $sourceConnectionData = new ConnectionData('bim_source', new SqliteDriverData($sourceDb));

    $usersTableConfig = new KeyRemappingTableData(
        table: 'users',
        primaryKey: 'id',
        strategy: KeyRemappingStrategyEnum::RandomInteger,
        rangeMin: 100000,
        rangeMax: 9999999,
        foreignKeys: new Collection(),
    );

    $keyRemappingConfig = new KeyRemappingConfigData(
        enabled: true,
        tables: new Collection([$usersTableConfig]),
    );

    $options = SynchronizationOptionsData::from([
        'tables' => [],
        'key_remapping' => ['enabled' => true, 'tables' => []],
    ]);

    $batch = Bus::batch([])->dispatch();

    $job = new BuildKeyMappingJob(
        sourceConnectionData: $sourceConnectionData,
        keyRemappingConfig: $keyRemappingConfig,
        options: $options,
        run: $run,
    );
    $job->withBatchId($batch->id);

    $job->handle(
        resolve(DatabaseInformationRetrievalService::class),
        resolve(KeyMappingRepository::class),
        resolve(KeyRemappingService::class),
    );

    $repository = resolve(KeyMappingRepository::class);
    $mappings = $repository->getAllMappings($run, 'users', 'id');
    expect($mappings)->toHaveCount(3);

    foreach ([1, 2, 3] as $originalId) {
        expect($mappings)->toHaveKey((string) $originalId);
        expect((int) $mappings[(string) $originalId])->not->toBe($originalId);
    }

    @unlink($sourceDb);
});

it('cleanup job deletes all mapping records for the run', function (): void {
    $run = CloningRun::factory()->create();
    $repository = resolve(KeyMappingRepository::class);

    $repository->insertMappings($run, 'users', 'id', ['1' => '999999', '2' => '888888']);

    expect($repository->getAllMappings($run, 'users', 'id'))->toHaveCount(2);

    $batch = Bus::batch([])->dispatch();
    $job = new CleanupKeyMappingJob($run);
    $job->withBatchId($batch->id);
    $job->handle($repository);

    expect($repository->getAllMappings($run, 'users', 'id'))->toHaveCount(0);
});
