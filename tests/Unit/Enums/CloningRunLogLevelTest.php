<?php

declare(strict_types=1);

use App\Enums\CloningRunLogLevel;

it('has all expected cases', function (): void {
    expect(CloningRunLogLevel::cases())->toHaveCount(3);
});

it('has correct string values', function (): void {
    expect(CloningRunLogLevel::INFO->value)->toBe('info')
        ->and(CloningRunLogLevel::WARNING->value)->toBe('warning')
        ->and(CloningRunLogLevel::ERROR->value)->toBe('error');
});

it('returns correct labels', function (): void {
    expect(CloningRunLogLevel::INFO->getLabel())->toBe('info')
        ->and(CloningRunLogLevel::WARNING->getLabel())->toBe('warning')
        ->and(CloningRunLogLevel::ERROR->getLabel())->toBe('error');
});

it('can be created from string value', function (): void {
    expect(CloningRunLogLevel::from('info'))->toBe(CloningRunLogLevel::INFO)
        ->and(CloningRunLogLevel::from('warning'))->toBe(CloningRunLogLevel::WARNING)
        ->and(CloningRunLogLevel::from('error'))->toBe(CloningRunLogLevel::ERROR);
});

it('throws exception for invalid value', function (): void {
    CloningRunLogLevel::from('invalid');
})->throws(ValueError::class);
