<?php

declare(strict_types=1);

use App\Models\Cloning;
use App\Models\CloningRun;
use Illuminate\Support\Facades\Bus;

it('does nothing when no scheduled clonings are due', function (): void {
    // Create a scheduled cloning but with future next_run_at
    Cloning::factory()->scheduled()->create([
        'next_run_at' => now()->addDay(),
    ]);

    $this->artisan('clonings:run-scheduled')
        ->expectsOutput('No scheduled clonings due to run.')
        ->assertSuccessful();

    expect(CloningRun::query()->count())->toBe(0);
});

it('executes scheduled clonings that are due', function (): void {
    Bus::fake();

    $cloning = Cloning::factory()->scheduled()->create([
        'next_run_at' => now()->subMinute(),
    ]);

    $this->artisan('clonings:run-scheduled')
        ->expectsOutputToContain('Found 1 cloning(s) due to run.')
        ->expectsOutputToContain("Executing cloning: {$cloning->title}")
        ->assertSuccessful();

    expect(CloningRun::query()->count())->toBe(1);
    Bus::assertBatched(fn ($batch): bool => $batch->name === "Scheduled sync: {$cloning->title}");
});

it('executes scheduled clonings with null next_run_at', function (): void {
    Bus::fake();

    $cloning = Cloning::factory()->scheduled()->create([
        'next_run_at' => null,
    ]);

    $this->artisan('clonings:run-scheduled')
        ->assertSuccessful();

    expect(CloningRun::query()->count())->toBe(1);
});

it('updates next_run_at after execution', function (): void {
    Bus::fake();

    $cloning = Cloning::factory()->scheduled('0 0 * * *')->create([
        'next_run_at' => now()->subMinute(),
    ]);

    $this->artisan('clonings:run-scheduled')
        ->assertSuccessful();

    $cloning->refresh();

    // Next run should be in the future (tomorrow at midnight for '0 0 * * *')
    expect($cloning->next_run_at)->not->toBeNull()
        ->and($cloning->next_run_at->isFuture())->toBeTrue();
});

it('skips clonings that are not scheduled', function (): void {
    Cloning::factory()->create([
        'is_scheduled' => false,
        'schedule' => '0 0 * * *',
        'next_run_at' => now()->subMinute(),
    ]);

    $this->artisan('clonings:run-scheduled')
        ->expectsOutput('No scheduled clonings due to run.')
        ->assertSuccessful();

    expect(CloningRun::query()->count())->toBe(0);
});

it('executes multiple scheduled clonings', function (): void {
    Bus::fake();

    Cloning::factory()->count(3)->scheduled()->create([
        'next_run_at' => now()->subMinute(),
    ]);

    $this->artisan('clonings:run-scheduled')
        ->expectsOutputToContain('Found 3 cloning(s) due to run.')
        ->assertSuccessful();

    expect(CloningRun::query()->count())->toBe(3);
});
