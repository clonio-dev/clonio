<?php

declare(strict_types=1);

use App\Data\RowSelectionStrategyEnum;

it('has all expected cases', function (): void {
    expect(RowSelectionStrategyEnum::cases())->toHaveCount(3);
});

it('has correct string values', function (): void {
    expect(RowSelectionStrategyEnum::FullTable->value)->toBe('full_table')
        ->and(RowSelectionStrategyEnum::FirstX->value)->toBe('first_x')
        ->and(RowSelectionStrategyEnum::LastX->value)->toBe('last_x');
});

it('can be created from string value', function (): void {
    expect(RowSelectionStrategyEnum::from('full_table'))->toBe(RowSelectionStrategyEnum::FullTable)
        ->and(RowSelectionStrategyEnum::from('first_x'))->toBe(RowSelectionStrategyEnum::FirstX)
        ->and(RowSelectionStrategyEnum::from('last_x'))->toBe(RowSelectionStrategyEnum::LastX);
});

it('returns null for invalid value with tryFrom', function (): void {
    expect(RowSelectionStrategyEnum::tryFrom('invalid'))->toBeNull();
});

it('throws exception for invalid value with from', function (): void {
    RowSelectionStrategyEnum::from('invalid');
})->throws(ValueError::class);
