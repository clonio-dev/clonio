<?php

declare(strict_types=1);

use App\Data\KeyRemappingConfigData;
use App\Data\KeyRemappingForeignKeyData;
use App\Data\KeyRemappingStrategyEnum;
use App\Data\KeyRemappingTableData;
use App\Models\CloningRun;
use App\Services\KeyMappingRepository;
use App\Services\KeyRemappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

function makeTableData(string $table, string $pk, KeyRemappingStrategyEnum $strategy = KeyRemappingStrategyEnum::RandomInteger, array $foreignKeys = []): KeyRemappingTableData
{
    return new KeyRemappingTableData(
        table: $table,
        primaryKey: $pk,
        strategy: $strategy,
        rangeMin: 100000,
        rangeMax: 9999999,
        foreignKeys: new Collection($foreignKeys),
    );
}

it('generates random integer mappings that differ from originals', function (): void {
    $run = CloningRun::factory()->create();
    $service = resolve(KeyRemappingService::class);

    $tableConfig = makeTableData('users', 'id');
    $mappings = $service->buildMappingForTable($run, $tableConfig, [1, 2, 3]);

    expect($mappings)->toHaveCount(3);

    foreach ([1, 2, 3] as $original) {
        $newValue = $mappings[(string) $original];
        expect((int) $newValue)->not->toBe($original);
        expect((int) $newValue)->toBeGreaterThanOrEqual(100000)->toBeLessThanOrEqual(9999999);
    }
});

it('generates unique new values without collisions', function (): void {
    $run = CloningRun::factory()->create();
    $service = resolve(KeyRemappingService::class);

    $tableConfig = makeTableData('users', 'id');
    $originalIds = range(1, 100);
    $mappings = $service->buildMappingForTable($run, $tableConfig, $originalIds);

    $newValues = array_values($mappings);
    expect(array_unique($newValues))->toHaveCount(count($newValues));
});

it('generates uuid mappings for new_uuid strategy', function (): void {
    $run = CloningRun::factory()->create();
    $service = resolve(KeyRemappingService::class);

    $tableConfig = makeTableData('products', 'uuid', KeyRemappingStrategyEnum::NewUuid);
    $originalUuid = '550e8400-e29b-41d4-a716-446655440000';
    $mappings = $service->buildMappingForTable($run, $tableConfig, [$originalUuid]);

    expect($mappings)->toHaveCount(1);
    expect($mappings[$originalUuid])->not->toBe($originalUuid);
    expect($mappings[$originalUuid])->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
});

it('applies primary key remapping to a row', function (): void {
    $run = CloningRun::factory()->create();
    $service = resolve(KeyRemappingService::class);

    $tableConfig = makeTableData('users', 'id');
    $service->buildMappingForTable($run, $tableConfig, [42]);

    $config = new KeyRemappingConfigData(true, new Collection([$tableConfig]));
    $row = ['id' => 42, 'name' => 'Alice'];

    $result = $service->applyMapping($row, $config, 'users', $run);

    expect($result['row']['id'])->not->toBe(42);
    expect($result['row']['name'])->toBe('Alice');
    expect($result['unmappedFks'])->toBe(0);
});

it('applies foreign key remapping to a row', function (): void {
    $run = CloningRun::factory()->create();
    $repository = resolve(KeyMappingRepository::class);
    $service = resolve(KeyRemappingService::class);

    $usersConfig = makeTableData('users', 'id', KeyRemappingStrategyEnum::RandomInteger, [
        new KeyRemappingForeignKeyData(table: 'posts', column: 'user_id'),
    ]);
    $service->buildMappingForTable($run, $usersConfig, [1, 2]);

    $mappings = $repository->getAllMappings($run, 'users', 'id');
    $newUserId1 = $mappings['1'];

    $config = new KeyRemappingConfigData(true, new Collection([$usersConfig]));
    $row = ['id' => 99, 'user_id' => 1, 'title' => 'Post 1'];

    $result = $service->applyMapping($row, $config, 'posts', $run);

    expect($result['row']['user_id'])->toBe((int) $newUserId1);
    expect($result['row']['title'])->toBe('Post 1');
    expect($result['unmappedFks'])->toBe(0);
});

it('counts unmapped FK values', function (): void {
    $run = CloningRun::factory()->create();
    $service = resolve(KeyRemappingService::class);

    $usersConfig = makeTableData('users', 'id', KeyRemappingStrategyEnum::RandomInteger, [
        new KeyRemappingForeignKeyData(table: 'posts', column: 'user_id'),
    ]);
    // Only map user_id = 1, not 999
    $service->buildMappingForTable($run, $usersConfig, [1]);

    $config = new KeyRemappingConfigData(true, new Collection([$usersConfig]));
    $row = ['id' => 50, 'user_id' => 999, 'title' => 'Orphan Post'];

    $result = $service->applyMapping($row, $config, 'posts', $run);

    expect($result['row']['user_id'])->toBe(999); // unchanged
    expect($result['unmappedFks'])->toBe(1);
});

it('leaves null FK values unchanged', function (): void {
    $run = CloningRun::factory()->create();
    $service = resolve(KeyRemappingService::class);

    $usersConfig = makeTableData('users', 'id', KeyRemappingStrategyEnum::RandomInteger, [
        new KeyRemappingForeignKeyData(table: 'posts', column: 'user_id'),
    ]);

    $config = new KeyRemappingConfigData(true, new Collection([$usersConfig]));
    $row = ['id' => 50, 'user_id' => null, 'title' => 'No Author'];

    $result = $service->applyMapping($row, $config, 'posts', $run);

    expect($result['row']['user_id'])->toBeNull();
    expect($result['unmappedFks'])->toBe(0);
});
