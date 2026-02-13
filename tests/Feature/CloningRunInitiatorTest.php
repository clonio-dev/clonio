<?php

declare(strict_types=1);

use App\Models\Cloning;
use App\Models\CloningRun;
use App\Models\CloningRunLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->cloning = Cloning::factory()->for($this->user)->create();
});

describe('CloningRun initiator accessor', function (): void {
    it('returns "user" when first log is user_initiated', function (): void {
        $run = CloningRun::factory()->for($this->cloning)->for($this->user)->create();

        CloningRunLog::factory()->for($run, 'run')->create([
            'event_type' => 'user_initiated',
        ]);

        expect($run->initiator)->toBe('user');
    });

    it('returns "api" when first log is api_triggered', function (): void {
        $run = CloningRun::factory()->for($this->cloning)->for($this->user)->create();

        CloningRunLog::factory()->for($run, 'run')->create([
            'event_type' => 'api_triggered',
        ]);

        expect($run->initiator)->toBe('api');
    });

    it('returns "scheduler" when first log is scheduled_cloning_run_created', function (): void {
        $run = CloningRun::factory()->for($this->cloning)->for($this->user)->create();

        CloningRunLog::factory()->for($run, 'run')->create([
            'event_type' => 'scheduled_cloning_run_created',
        ]);

        expect($run->initiator)->toBe('scheduler');
    });

    it('returns "manual" when no logs exist', function (): void {
        $run = CloningRun::factory()->for($this->cloning)->for($this->user)->create();

        expect($run->initiator)->toBe('manual');
    });

    it('returns "manual" when only non-initiator logs exist', function (): void {
        $run = CloningRun::factory()->for($this->cloning)->for($this->user)->create();

        CloningRunLog::factory()->for($run, 'run')->create([
            'event_type' => 'batch_started',
        ]);

        expect($run->initiator)->toBe('manual');
    });

    it('finds initiator log even when cloning_run_created is the first log', function (): void {
        $run = CloningRun::factory()->for($this->cloning)->for($this->user)->create();

        CloningRunLog::factory()->for($run, 'run')->create([
            'event_type' => 'cloning_run_created',
        ]);

        CloningRunLog::factory()->for($run, 'run')->create([
            'event_type' => 'api_triggered',
        ]);

        $run->unsetRelation('initiatorLog');

        expect($run->initiator)->toBe('api');
    });

    it('finds scheduler initiator after cloning_run_created log', function (): void {
        $run = CloningRun::factory()->for($this->cloning)->for($this->user)->create();

        CloningRunLog::factory()->for($run, 'run')->create([
            'event_type' => 'cloning_run_created',
        ]);

        CloningRunLog::factory()->for($run, 'run')->create([
            'event_type' => 'scheduled_cloning_run_created',
        ]);

        $run->unsetRelation('initiatorLog');

        expect($run->initiator)->toBe('scheduler');
    });
});

describe('CloningRunController@index includes initiator', function (): void {
    it('includes initiator in the response for each run', function (): void {
        $run = CloningRun::factory()->for($this->cloning)->for($this->user)->create();

        CloningRunLog::factory()->for($run, 'run')->create([
            'event_type' => 'scheduled_cloning_run_created',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/cloning-runs');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('cloning-runs/Index')
            ->has('runs.data', 1)
            ->where('runs.data.0.initiator', 'scheduler')
        );
    });
});
