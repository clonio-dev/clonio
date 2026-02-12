<?php

declare(strict_types=1);

use App\Models\Cloning;
use App\Models\CloningRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

describe('CloningController@destroyFailedRuns', function (): void {
    it('deletes all failed runs for a cloning', function (): void {
        $cloning = Cloning::factory()->for($this->user)->create();

        CloningRun::factory()->for($cloning)->for($this->user)->failed()->count(3)->create();
        CloningRun::factory()->for($cloning)->for($this->user)->completed()->count(2)->create();

        $response = $this->actingAs($this->user)
            ->delete("/clonings/{$cloning->id}/failed-runs");

        $response->assertRedirect();
        $response->assertSessionHas('success', '3 failed run(s) deleted');

        expect(CloningRun::query()->where('cloning_id', $cloning->id)->count())->toBe(2);
    });

    it('does not delete runs from another cloning', function (): void {
        $cloning = Cloning::factory()->for($this->user)->create();
        $otherCloning = Cloning::factory()->for($this->user)->create();

        CloningRun::factory()->for($cloning)->for($this->user)->failed()->count(2)->create();
        CloningRun::factory()->for($otherCloning)->for($this->user)->failed()->count(3)->create();

        $this->actingAs($this->user)
            ->delete("/clonings/{$cloning->id}/failed-runs");

        expect(CloningRun::query()->where('cloning_id', $otherCloning->id)->count())->toBe(3);
    });

    it('forbids deleting failed runs from another users cloning', function (): void {
        $cloning = Cloning::factory()->create();
        CloningRun::factory()->for($cloning)->failed()->count(2)->create();

        $response = $this->actingAs($this->user)
            ->delete("/clonings/{$cloning->id}/failed-runs");

        $response->assertForbidden();
    });

    it('requires authentication', function (): void {
        $cloning = Cloning::factory()->create();

        $response = $this->delete("/clonings/{$cloning->id}/failed-runs");

        $response->assertRedirect('/login');
    });
});

describe('CloningRunController@destroyFailed', function (): void {
    it('deletes all failed runs for the authenticated user', function (): void {
        $cloning = Cloning::factory()->for($this->user)->create();

        CloningRun::factory()->for($cloning)->for($this->user)->failed()->count(4)->create();
        CloningRun::factory()->for($cloning)->for($this->user)->completed()->count(2)->create();

        $response = $this->actingAs($this->user)
            ->delete('/cloning-runs/failed');

        $response->assertRedirect();
        $response->assertSessionHas('success', '4 failed run(s) deleted');

        expect(CloningRun::query()->where('user_id', $this->user->id)->count())->toBe(2);
    });

    it('does not delete failed runs from other users', function (): void {
        $otherUser = User::factory()->create();
        $otherCloning = Cloning::factory()->for($otherUser)->create();

        CloningRun::factory()->for($otherCloning)->for($otherUser)->failed()->count(3)->create();

        $cloning = Cloning::factory()->for($this->user)->create();
        CloningRun::factory()->for($cloning)->for($this->user)->failed()->count(1)->create();

        $this->actingAs($this->user)
            ->delete('/cloning-runs/failed');

        expect(CloningRun::query()->where('user_id', $otherUser->id)->count())->toBe(3);
    });

    it('requires authentication', function (): void {
        $response = $this->delete('/cloning-runs/failed');

        $response->assertRedirect('/login');
    });
});
