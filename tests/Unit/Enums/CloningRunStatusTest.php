<?php

declare(strict_types=1);

use App\Enums\CloningRunStatus;

it('has all expected cases', function (): void {
    expect(CloningRunStatus::cases())->toHaveCount(5);
});

it('has correct string values', function (): void {
    expect(CloningRunStatus::QUEUED->value)->toBe('queued')
        ->and(CloningRunStatus::PROCESSING->value)->toBe('processing')
        ->and(CloningRunStatus::COMPLETED->value)->toBe('completed')
        ->and(CloningRunStatus::FAILED->value)->toBe('failed')
        ->and(CloningRunStatus::CANCELLED->value)->toBe('cancelled');
});

it('returns correct labels', function (): void {
    expect(CloningRunStatus::QUEUED->getLabel())->toBe('queued')
        ->and(CloningRunStatus::PROCESSING->getLabel())->toBe('processing')
        ->and(CloningRunStatus::COMPLETED->getLabel())->toBe('completed')
        ->and(CloningRunStatus::FAILED->getLabel())->toBe('failed')
        ->and(CloningRunStatus::CANCELLED->getLabel())->toBe('cancelled by user');
});

it('can be created from string value', function (): void {
    expect(CloningRunStatus::from('queued'))->toBe(CloningRunStatus::QUEUED)
        ->and(CloningRunStatus::from('processing'))->toBe(CloningRunStatus::PROCESSING)
        ->and(CloningRunStatus::from('completed'))->toBe(CloningRunStatus::COMPLETED)
        ->and(CloningRunStatus::from('failed'))->toBe(CloningRunStatus::FAILED)
        ->and(CloningRunStatus::from('cancelled'))->toBe(CloningRunStatus::CANCELLED);
});

it('throws exception for invalid value', function (): void {
    CloningRunStatus::from('invalid');
})->throws(ValueError::class);
