<?php

declare(strict_types=1);

use App\Models\Cloning;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('canRun', function (): void {
    it('returns true when scheduled and not paused', function (): void {
        $cloning = Cloning::factory()->scheduled()->create();

        expect($cloning->canRun())->toBeTrue();
    });

    it('returns false when paused', function (): void {
        $cloning = Cloning::factory()->scheduled()->paused()->create();

        expect($cloning->canRun())->toBeFalse();
    });

    it('returns false when not scheduled', function (): void {
        $cloning = Cloning::factory()->create(['is_scheduled' => false]);

        expect($cloning->canRun())->toBeFalse();
    });
});

describe('pause', function (): void {
    it('sets is_paused to true', function (): void {
        $cloning = Cloning::factory()->scheduled()->create();

        $cloning->pause();

        expect($cloning->fresh()->is_paused)->toBeTrue();
    });
});

describe('resume', function (): void {
    it('sets is_paused to false and resets consecutive_failures', function (): void {
        $cloning = Cloning::factory()
            ->scheduled()
            ->paused()
            ->withConsecutiveFailures(3)
            ->create();

        $cloning->resume();

        $cloning->refresh();
        expect($cloning->is_paused)->toBeFalse()
            ->and($cloning->consecutive_failures)->toBe(0);
    });
});

describe('recordFailure', function (): void {
    it('increments consecutive_failures', function (): void {
        $cloning = Cloning::factory()->scheduled()->create();

        $cloning->recordFailure();

        expect($cloning->fresh()->consecutive_failures)->toBe(1);
    });

    it('auto-pauses at 3 consecutive failures', function (): void {
        $cloning = Cloning::factory()
            ->scheduled()
            ->withConsecutiveFailures(2)
            ->create();

        $wasPaused = $cloning->recordFailure();

        $cloning->refresh();
        expect($wasPaused)->toBeTrue()
            ->and($cloning->consecutive_failures)->toBe(3)
            ->and($cloning->is_paused)->toBeTrue();
    });

    it('returns false when threshold not reached', function (): void {
        $cloning = Cloning::factory()->scheduled()->create();

        $wasPaused = $cloning->recordFailure();

        expect($wasPaused)->toBeFalse()
            ->and($cloning->fresh()->is_paused)->toBeFalse();
    });
});

describe('recordSuccess', function (): void {
    it('resets consecutive_failures to zero', function (): void {
        $cloning = Cloning::factory()
            ->scheduled()
            ->withConsecutiveFailures(2)
            ->create();

        $cloning->recordSuccess();

        expect($cloning->fresh()->consecutive_failures)->toBe(0);
    });

    it('does nothing when consecutive_failures is already zero', function (): void {
        $cloning = Cloning::factory()->scheduled()->create();

        $cloning->recordSuccess();

        expect($cloning->fresh()->consecutive_failures)->toBe(0);
    });
});

describe('notPaused scope', function (): void {
    it('excludes paused clonings', function (): void {
        Cloning::factory()->scheduled()->paused()->create();
        $active = Cloning::factory()->scheduled()->create();

        $results = Cloning::query()->notPaused()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->id)->toBe($active->id);
    });
});
