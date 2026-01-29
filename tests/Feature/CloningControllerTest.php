<?php

declare(strict_types=1);

use App\Models\Cloning;
use App\Models\DatabaseConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

describe('index', function (): void {
    it('shows clonings list for authenticated user', function (): void {
        $clonings = Cloning::factory()
            ->count(3)
            ->for($this->user)
            ->create();

        $response = $this->actingAs($this->user)
            ->get('/clonings');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('clonings/Index')
            ->has('clonings.data', 3));
    });

    it('only shows clonings belonging to the authenticated user', function (): void {
        // Create clonings for other user
        Cloning::factory()->count(2)->create();

        // Create cloning for current user
        Cloning::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->get('/clonings');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('clonings.data', 1));
    });

    it('requires authentication', function (): void {
        $response = $this->get('/clonings');

        $response->assertRedirect('/login');
    });
});

describe('create', function (): void {
    it('shows create form', function (): void {
        $response = $this->actingAs($this->user)
            ->get('/clonings/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('clonings/Create')
            ->has('prod_connections')
            ->has('test_connections'));
    });

    it('filters connections by production stage', function (): void {
        DatabaseConnection::factory()
            ->for($this->user)
            ->mysql()
            ->create(['name' => 'Prod DB']);

        DatabaseConnection::factory()
            ->for($this->user)
            ->mysql()
            ->testDatabase()
            ->create(['name' => 'Test DB']);

        $response = $this->actingAs($this->user)
            ->get('/clonings/create');

        $response->assertInertia(fn ($page) => $page
            ->where('prod_connections.0.label', 'Prod DB')
            ->where('test_connections.0.label', 'Test DB'));
    });
});

describe('store', function (): void {
    it('creates a new cloning', function (): void {
        $source = DatabaseConnection::factory()->for($this->user)->mysql()->create();
        $target = DatabaseConnection::factory()->for($this->user)->mysql()->testDatabase()->create();

        $response = $this->actingAs($this->user)
            ->post('/clonings', [
                'title' => 'My Test Cloning',
                'source_connection_id' => $source->id,
                'target_connection_id' => $target->id,
                'execute_now' => false,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('clonings', [
            'title' => 'My Test Cloning',
            'user_id' => $this->user->id,
            'source_connection_id' => $source->id,
            'target_connection_id' => $target->id,
        ]);
    });

    it('validates required fields', function (): void {
        $response = $this->actingAs($this->user)
            ->post('/clonings', []);

        $response->assertSessionHasErrors(['title', 'source_connection_id', 'target_connection_id']);
    });

    it('validates source connection belongs to user', function (): void {
        $otherUser = User::factory()->create();
        $source = DatabaseConnection::factory()->for($otherUser)->mysql()->create();
        $target = DatabaseConnection::factory()->for($this->user)->mysql()->testDatabase()->create();

        $response = $this->actingAs($this->user)
            ->post('/clonings', [
                'title' => 'Test Cloning',
                'source_connection_id' => $source->id,
                'target_connection_id' => $target->id,
            ]);

        $response->assertSessionHasErrors('source_connection_id');
    });
});

describe('show', function (): void {
    it('shows cloning details', function (): void {
        $cloning = Cloning::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->get("/clonings/{$cloning->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('clonings/Show')
            ->has('cloning')
            ->has('runs'));
    });

    it('forbids access to other users cloning', function (): void {
        $cloning = Cloning::factory()->create();

        $response = $this->actingAs($this->user)
            ->get("/clonings/{$cloning->id}");

        $response->assertForbidden();
    });
});

describe('edit', function (): void {
    it('shows edit form', function (): void {
        $cloning = Cloning::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->get("/clonings/{$cloning->id}/edit");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('clonings/Edit')
            ->has('cloning')
            ->has('prod_connections')
            ->has('test_connections'));
    });

    it('forbids editing other users cloning', function (): void {
        $cloning = Cloning::factory()->create();

        $response = $this->actingAs($this->user)
            ->get("/clonings/{$cloning->id}/edit");

        $response->assertForbidden();
    });
});

describe('update', function (): void {
    it('updates cloning', function (): void {
        // Create connections owned by the user
        $source = DatabaseConnection::factory()->for($this->user)->mysql()->create();
        $target = DatabaseConnection::factory()->for($this->user)->mysql()->testDatabase()->create();

        $cloning = Cloning::factory()->for($this->user)->create([
            'source_connection_id' => $source->id,
            'target_connection_id' => $target->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put("/clonings/{$cloning->id}", [
                'title' => 'Updated Title',
                'source_connection_id' => $source->id,
                'target_connection_id' => $target->id,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('clonings', [
            'id' => $cloning->id,
            'title' => 'Updated Title',
        ]);
    });

    it('forbids updating other users cloning', function (): void {
        // Create connections owned by the user to pass validation
        $source = DatabaseConnection::factory()->for($this->user)->mysql()->create();
        $target = DatabaseConnection::factory()->for($this->user)->mysql()->testDatabase()->create();

        // Cloning belongs to another user
        $cloning = Cloning::factory()->create();

        $response = $this->actingAs($this->user)
            ->put("/clonings/{$cloning->id}", [
                'title' => 'Hacked',
                'source_connection_id' => $source->id,
                'target_connection_id' => $target->id,
            ]);

        $response->assertForbidden();
    });
});

describe('destroy', function (): void {
    it('deletes cloning', function (): void {
        $cloning = Cloning::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->delete("/clonings/{$cloning->id}");

        $response->assertRedirect('/clonings');
        $this->assertDatabaseMissing('clonings', ['id' => $cloning->id]);
    });

    it('forbids deleting other users cloning', function (): void {
        $cloning = Cloning::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete("/clonings/{$cloning->id}");

        $response->assertForbidden();
    });
});

describe('execute', function (): void {
    it('creates a run and redirects to it', function (): void {
        $cloning = Cloning::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)
            ->post("/clonings/{$cloning->id}/execute");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Cloning execution started');

        $this->assertDatabaseHas('cloning_runs', [
            'cloning_id' => $cloning->id,
            'user_id' => $this->user->id,
        ]);
    });

    it('forbids executing other users cloning', function (): void {
        $cloning = Cloning::factory()->create();

        $response = $this->actingAs($this->user)
            ->post("/clonings/{$cloning->id}/execute");

        $response->assertForbidden();
    });
});

describe('pause', function (): void {
    it('pauses a scheduled cloning', function (): void {
        $cloning = Cloning::factory()->for($this->user)->scheduled()->create();

        $response = $this->actingAs($this->user)
            ->post("/clonings/{$cloning->id}/pause");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Cloning paused');

        expect($cloning->fresh()->is_paused)->toBeTrue();
    });

    it('forbids pausing other users cloning', function (): void {
        $cloning = Cloning::factory()->scheduled()->create();

        $response = $this->actingAs($this->user)
            ->post("/clonings/{$cloning->id}/pause");

        $response->assertForbidden();
    });
});

describe('resume', function (): void {
    it('resumes a paused cloning and resets failure count', function (): void {
        $cloning = Cloning::factory()
            ->for($this->user)
            ->scheduled()
            ->paused()
            ->withConsecutiveFailures(3)
            ->create();

        $response = $this->actingAs($this->user)
            ->post("/clonings/{$cloning->id}/resume");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Cloning resumed');

        $cloning->refresh();
        expect($cloning->is_paused)->toBeFalse()
            ->and($cloning->consecutive_failures)->toBe(0);
    });

    it('forbids resuming other users cloning', function (): void {
        $cloning = Cloning::factory()->scheduled()->paused()->create();

        $response = $this->actingAs($this->user)
            ->post("/clonings/{$cloning->id}/resume");

        $response->assertForbidden();
    });
});
