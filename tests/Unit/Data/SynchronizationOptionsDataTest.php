<?php

declare(strict_types=1);

use App\Data\SynchronizationOptionsData;
use App\Data\SynchronizeTableSchemaEnum;

it('can be created with default values', function (): void {
    $synchronizationOptionsData = new SynchronizationOptionsData();

    expect($synchronizationOptionsData->chunkSize)->toBe(1000);
    expect($synchronizationOptionsData->migrationTableName)->toBeNull();
    expect($synchronizationOptionsData->synchronizeTableSchema)->toBe(SynchronizeTableSchemaEnum::DROP_CREATE);
    expect($synchronizationOptionsData->keepUnknownTablesOnTarget)->toBeTrue();
    expect($synchronizationOptionsData->disableForeignKeyConstraints)->toBeTrue();
});
