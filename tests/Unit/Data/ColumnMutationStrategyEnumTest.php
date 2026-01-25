<?php

declare(strict_types=1);

use App\Data\ColumnMutationStrategyEnum;

it('has all expected cases', function (): void {
    expect(ColumnMutationStrategyEnum::cases())->toHaveCount(6);
});

it('has correct string values', function (): void {
    expect(ColumnMutationStrategyEnum::KEEP->value)->toBe('keep')
        ->and(ColumnMutationStrategyEnum::FAKE->value)->toBe('fake')
        ->and(ColumnMutationStrategyEnum::MASK->value)->toBe('mask')
        ->and(ColumnMutationStrategyEnum::HASH->value)->toBe('hash')
        ->and(ColumnMutationStrategyEnum::NULL->value)->toBe('null')
        ->and(ColumnMutationStrategyEnum::STATIC->value)->toBe('static');
});

it('can be created from string value', function (): void {
    expect(ColumnMutationStrategyEnum::from('keep'))->toBe(ColumnMutationStrategyEnum::KEEP)
        ->and(ColumnMutationStrategyEnum::from('fake'))->toBe(ColumnMutationStrategyEnum::FAKE)
        ->and(ColumnMutationStrategyEnum::from('mask'))->toBe(ColumnMutationStrategyEnum::MASK)
        ->and(ColumnMutationStrategyEnum::from('hash'))->toBe(ColumnMutationStrategyEnum::HASH)
        ->and(ColumnMutationStrategyEnum::from('null'))->toBe(ColumnMutationStrategyEnum::NULL)
        ->and(ColumnMutationStrategyEnum::from('static'))->toBe(ColumnMutationStrategyEnum::STATIC);
});

it('throws exception for invalid value', function (): void {
    ColumnMutationStrategyEnum::from('invalid');
})->throws(ValueError::class);
