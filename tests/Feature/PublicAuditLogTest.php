<?php

declare(strict_types=1);

use App\Models\Cloning;
use App\Models\CloningRun;
use App\Models\DatabaseConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('displays audit trail for valid public token', function (): void {
    $user = User::factory()->create();

    $source = DatabaseConnection::factory()->for($user)->sqlite()->create();
    $target = DatabaseConnection::factory()->for($user)->sqlite()->testDatabase()->create();

    $cloning = Cloning::factory()
        ->for($user)
        ->create([
            'source_connection_id' => $source->id,
            'target_connection_id' => $target->id,
        ]);

    $publicToken = bin2hex(random_bytes(32));

    $run = CloningRun::factory()
        ->for($user)
        ->for($cloning)
        ->completed()
        ->create([
            'public_token' => $publicToken,
            'config_snapshot' => ['tables' => [], 'keepUnknownTablesOnTarget' => false, 'version' => 1],
            'audit_hash' => hash('sha256', 'test'),
            'audit_signature' => hash_hmac('sha256', hash('sha256', 'test'), 'test-secret'),
            'audit_signed_at' => now(),
        ]);

    $response = $this->get('/audit/' . $publicToken);

    $response->assertOk();
    $response->assertSee('Audit Trail Report');
    $response->assertSee('Transfer Run #' . $run->id);
});

it('returns 404 for invalid public token', function (): void {
    $response = $this->get('/audit/invalid-token-that-does-not-exist');

    $response->assertNotFound();
});

it('is accessible without authentication', function (): void {
    $user = User::factory()->create();

    $source = DatabaseConnection::factory()->for($user)->sqlite()->create();
    $target = DatabaseConnection::factory()->for($user)->sqlite()->testDatabase()->create();

    $cloning = Cloning::factory()
        ->for($user)
        ->create([
            'source_connection_id' => $source->id,
            'target_connection_id' => $target->id,
        ]);

    $publicToken = bin2hex(random_bytes(32));

    CloningRun::factory()
        ->for($user)
        ->for($cloning)
        ->completed()
        ->create([
            'public_token' => $publicToken,
            'config_snapshot' => ['tables' => [], 'keepUnknownTablesOnTarget' => false, 'version' => 1],
            'audit_hash' => hash('sha256', 'test'),
            'audit_signature' => hash_hmac('sha256', hash('sha256', 'test'), 'test-secret'),
            'audit_signed_at' => now(),
        ]);

    // No actingAs - not authenticated
    $response = $this->get('/audit/' . $publicToken);

    $response->assertOk();
});
