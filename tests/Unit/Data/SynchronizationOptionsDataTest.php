<?php

declare(strict_types=1);

use App\Data\SynchronizationOptionsData;

it('can be created with default values', function (): void {
    $synchronizationOptionsData = new SynchronizationOptionsData();

    expect($synchronizationOptionsData->chunkSize)->toBe(1000);
    expect($synchronizationOptionsData->migrationTableName)->toBeNull();
    expect($synchronizationOptionsData->keepUnknownTablesOnTarget)->toBeTrue();
    expect($synchronizationOptionsData->disableForeignKeyConstraints)->toBeTrue();
});
