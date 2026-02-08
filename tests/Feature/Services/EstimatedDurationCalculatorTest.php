<?php

declare(strict_types=1);

use App\Models\Cloning;
use App\Models\CloningRun;
use App\Services\EstimatedDurationCalculator;

it('returns null when no completed runs exist', function (): void {
    $cloning = Cloning::factory()->create();

    $result = resolve(EstimatedDurationCalculator::class)->calculate($cloning);

    expect($result)->toBeNull();
});

it('returns the duration of a single completed run', function (): void {
    $cloning = Cloning::factory()->create();

    CloningRun::factory()->completed()->create([
        'cloning_id' => $cloning->id,
        'user_id' => $cloning->user_id,
        'started_at' => now()->subMinutes(5),
        'finished_at' => now(),
    ]);

    $result = resolve(EstimatedDurationCalculator::class)->calculate($cloning);

    expect($result)->toBe(300);
});

it('weights recent runs higher than older runs', function (): void {
    $cloning = Cloning::factory()->create();

    // Older run: 100 seconds (weight 0.8)
    CloningRun::factory()->completed()->create([
        'cloning_id' => $cloning->id,
        'user_id' => $cloning->user_id,
        'started_at' => now()->subHour()->subSeconds(100),
        'finished_at' => now()->subHour(),
    ]);

    // Most recent run: 200 seconds (weight 1.0)
    CloningRun::factory()->completed()->create([
        'cloning_id' => $cloning->id,
        'user_id' => $cloning->user_id,
        'started_at' => now()->subSeconds(200),
        'finished_at' => now(),
    ]);

    $result = resolve(EstimatedDurationCalculator::class)->calculate($cloning);

    // Weighted average: (200*1.0 + 100*0.8) / (1.0 + 0.8) = 280 / 1.8 = 155.56 -> 156
    expect($result)->toBe(156);
});

it('ignores non-completed runs', function (): void {
    $cloning = Cloning::factory()->create();

    // Completed run: 120 seconds
    CloningRun::factory()->completed()->create([
        'cloning_id' => $cloning->id,
        'user_id' => $cloning->user_id,
        'started_at' => now()->subSeconds(120),
        'finished_at' => now(),
    ]);

    // Failed run
    CloningRun::factory()->failed()->create([
        'cloning_id' => $cloning->id,
        'user_id' => $cloning->user_id,
        'started_at' => now()->subSeconds(500),
        'finished_at' => now(),
    ]);

    // Cancelled run
    CloningRun::factory()->cancelled()->create([
        'cloning_id' => $cloning->id,
        'user_id' => $cloning->user_id,
        'started_at' => now()->subSeconds(300),
        'finished_at' => now(),
    ]);

    // Queued run (no finished_at)
    CloningRun::factory()->create([
        'cloning_id' => $cloning->id,
        'user_id' => $cloning->user_id,
    ]);

    // Processing run
    CloningRun::factory()->running()->create([
        'cloning_id' => $cloning->id,
        'user_id' => $cloning->user_id,
    ]);

    $result = resolve(EstimatedDurationCalculator::class)->calculate($cloning);

    expect($result)->toBe(120);
});

it('limits to 10 most recent runs', function (): void {
    $cloning = Cloning::factory()->create();

    // Create 12 completed runs, each 60 seconds
    for ($i = 0; $i < 12; $i++) {
        CloningRun::factory()->completed()->create([
            'cloning_id' => $cloning->id,
            'user_id' => $cloning->user_id,
            'started_at' => now()->subMinutes(12 - $i)->subSeconds(60),
            'finished_at' => now()->subMinutes(12 - $i),
        ]);
    }

    $result = resolve(EstimatedDurationCalculator::class)->calculate($cloning);

    // All durations are 60 seconds, so the weighted average should be 60
    expect($result)->toBe(60);
});
