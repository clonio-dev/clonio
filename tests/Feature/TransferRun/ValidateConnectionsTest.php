<?php

declare(strict_types=1);

use App\Models\DatabaseConnection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

it('requires authentication', function (): void {
    $response = postJson(route('transfers.validate-connections'), [
        'source_connection_id' => 1,
        'target_connection_id' => 2,
    ]);

    $response->assertUnauthorized();
});

it('validates required fields', function (): void {
    actingAs($this->user);

    $response = postJson(route('transfers.validate-connections'), []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'source_connection_id' => 'Please select a source connection.',
            'target_connection_id' => 'Please select a target connection.',
        ]);
});

it('validates connection exists and belongs to user', function (): void {
    actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherUsersConnection = DatabaseConnection::factory()
        ->for($otherUser)
        ->create();

    $response = postJson(route('transfers.validate-connections'), [
        'source_connection_id' => $otherUsersConnection->id,
        'target_connection_id' => 999,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors([
            'source_connection_id' => 'The selected source connection is invalid.',
            'target_connection_id' => 'The selected target connection is invalid.',
        ]);
});

it('validates connection can actually connect', function (): void {
    actingAs($this->user);

    // Create connections with invalid credentials
    $sourceConnection = DatabaseConnection::factory()
        ->for($this->user)
        ->mysql()
        ->create([
            'host' => 'invalid-host-that-does-not-exist',
            'database' => 'nonexistent_db',
        ]);

    $targetConnection = DatabaseConnection::factory()
        ->for($this->user)
        ->testDatabase()
        ->mysql()
        ->create([
            'host' => 'another-invalid-host',
            'database' => 'another_nonexistent_db',
        ]);

    $response = postJson(route('transfers.validate-connections'), [
        'source_connection_id' => $sourceConnection->id,
        'target_connection_id' => $targetConnection->id,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrorFor('source_connection_id');
});

it('returns schema data on successful validation with valid sqlite connections', function (): void {
    actingAs($this->user);

    // Create temporary SQLite databases
    $sourceDb = tempnam(sys_get_temp_dir(), 'source_');
    @unlink($sourceDb);
    $sourceDb .= '.sqlite';
    touch($sourceDb);

    $targetDb = tempnam(sys_get_temp_dir(), 'target_');
    @unlink($targetDb);
    $targetDb .= '.sqlite';
    touch($targetDb);

    // Set up source database with tables
    config(['database.connections.source_test' => [
        'driver' => 'sqlite',
        'database' => $sourceDb,
    ]]);
    DB::purge('source_test');

    DB::connection('source_test')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
    });

    // Set up target database with tables
    config(['database.connections.target_test' => [
        'driver' => 'sqlite',
        'database' => $targetDb,
    ]]);
    DB::purge('target_test');

    DB::connection('target_test')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
    });

    // Create database connections
    $sourceConnection = DatabaseConnection::factory()
        ->for($this->user)
        ->sqlite()
        ->create([
            'name' => 'source_test',
            'database' => $sourceDb,
            'host' => '',
        ]);

    $targetConnection = DatabaseConnection::factory()
        ->for($this->user)
        ->sqlite()
        ->testDatabase()
        ->create([
            'name' => 'target_test',
            'database' => $targetDb,
            'host' => '',
        ]);

    $response = $this->post(route('transfers.validate-connections'), [
        'source_connection_id' => $sourceConnection->id,
        'target_connection_id' => $targetConnection->id,
    ]);

    $response->assertRedirect()
        ->assertSessionHas('validated_connections');

    $data = session('validated_connections');

    expect($data['source_connection']['id'])->toBe($sourceConnection->id)
        ->and($data['target_connection']['id'])->toBe($targetConnection->id)
        ->and($data['source_schema'])->toHaveKey('users')
        ->and($data['target_schema'])->toHaveKey('users');

    // Check column structure
    $sourceColumns = collect($data['source_schema']['users']);
    expect($sourceColumns->pluck('name')->toArray())->toContain('id', 'name', 'email');

    // Clean up
    @unlink($sourceDb);
    @unlink($targetDb);
});

it('updates last_tested_at on successful validation', function (): void {
    actingAs($this->user);

    // Create temporary SQLite database
    $db = tempnam(sys_get_temp_dir(), 'test_');
    @unlink($db);
    $db .= '.sqlite';
    touch($db);

    config(['database.connections.test_validate' => [
        'driver' => 'sqlite',
        'database' => $db,
    ]]);
    DB::purge('test_validate');

    $sourceConnection = DatabaseConnection::factory()
        ->for($this->user)
        ->sqlite()
        ->create([
            'name' => 'test_validate',
            'database' => $db,
            'host' => '',
            'last_tested_at' => null,
        ]);

    $targetConnection = DatabaseConnection::factory()
        ->for($this->user)
        ->sqlite()
        ->testDatabase()
        ->create([
            'name' => 'test_validate2',
            'database' => $db,
            'host' => '',
            'last_tested_at' => null,
        ]);

    expect($sourceConnection->fresh()->last_tested_at)->toBeNull();
    expect($targetConnection->fresh()->last_tested_at)->toBeNull();

    $this->post(route('transfers.validate-connections'), [
        'source_connection_id' => $sourceConnection->id,
        'target_connection_id' => $targetConnection->id,
    ])->assertRedirect();

    expect($sourceConnection->fresh()->last_tested_at)->not->toBeNull();
    expect($targetConnection->fresh()->last_tested_at)->not->toBeNull();

    @unlink($db);
});
