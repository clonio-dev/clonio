<?php

declare(strict_types=1);

use App\Data\RowSelectionStrategyEnum;
use App\Data\TableRowSelectionData;

it('has correct defaults', function (): void {
    $data = new TableRowSelectionData();

    expect($data->strategy)->toBe(RowSelectionStrategyEnum::FullTable)
        ->and($data->limit)->toBe(1000)
        ->and($data->sortColumn)->toBeNull();
});

it('can be created with custom values', function (): void {
    $data = new TableRowSelectionData(
        strategy: RowSelectionStrategyEnum::FirstX,
        limit: 500,
        sortColumn: 'created_at',
    );

    expect($data->strategy)->toBe(RowSelectionStrategyEnum::FirstX)
        ->and($data->limit)->toBe(500)
        ->and($data->sortColumn)->toBe('created_at');
});

it('can be created with LastX strategy', function (): void {
    $data = new TableRowSelectionData(
        strategy: RowSelectionStrategyEnum::LastX,
        limit: 100,
        sortColumn: 'id',
    );

    expect($data->strategy)->toBe(RowSelectionStrategyEnum::LastX)
        ->and($data->limit)->toBe(100)
        ->and($data->sortColumn)->toBe('id');
});
