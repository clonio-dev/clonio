<?php

declare(strict_types=1);

use App\Models\DatabaseConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

describe('update', function (): void {
    it('updates a connection belonging to the user', function (): void {
        $connection = DatabaseConnection::factory()
            ->for($this->user)
            ->mysql()
            ->create([
                'name' => 'Old Name',
                'host' => 'old-host.example.com',
                'port' => 3306,
                'database' => 'old_db',
                'username' => 'old_user',
            ]);

        $response = $this->actingAs($this->user)
            ->put("/connections/{$connection->id}", [
                'name' => 'New Name',
                'type' => 'pgsql',
                'host' => 'new-host.example.com',
                'port' => 5432,
                'database' => 'new_db',
                'username' => 'new_user',
                'password' => 'new_password',
            ]);

        $response->assertRedirect(route('connections.index'));

        $connection->refresh();
        expect($connection->name)->toBe('New Name')
            ->and($connection->type->value)->toBe('pgsql')
            ->and($connection->host)->toBe('new-host.example.com')
            ->and($connection->port)->toBe(5432)
            ->and($connection->database)->toBe('new_db')
            ->and($connection->username)->toBe('new_user')
            ->and($connection->password)->toBe('new_password');
    });

    it('keeps the existing password when password is not provided', function (): void {
        $connection = DatabaseConnection::factory()
            ->for($this->user)
            ->mysql()
            ->create([
                'password' => 'original_password',
            ]);

        $this->actingAs($this->user)
            ->put("/connections/{$connection->id}", [
                'name' => $connection->name,
                'type' => $connection->type->value,
                'host' => $connection->host,
                'port' => $connection->port,
                'database' => $connection->database,
                'username' => $connection->username,
            ]);

        $connection->refresh();
        expect($connection->password)->toBe('original_password');
    });

    it('keeps the existing password when password is empty string', function (): void {
        $connection = DatabaseConnection::factory()
            ->for($this->user)
            ->mysql()
            ->create([
                'password' => 'original_password',
            ]);

        $this->actingAs($this->user)
            ->put("/connections/{$connection->id}", [
                'name' => $connection->name,
                'type' => $connection->type->value,
                'host' => $connection->host,
                'port' => $connection->port,
                'database' => $connection->database,
                'username' => $connection->username,
                'password' => '',
            ]);

        $connection->refresh();
        expect($connection->password)->toBe('original_password');
    });

    it('updates the production stage flag', function (): void {
        $connection = DatabaseConnection::factory()
            ->for($this->user)
            ->mysql()
            ->create([
                'is_production_stage' => false,
            ]);

        $this->actingAs($this->user)
            ->put("/connections/{$connection->id}", [
                'name' => $connection->name,
                'type' => $connection->type->value,
                'host' => $connection->host,
                'port' => $connection->port,
                'database' => $connection->database,
                'username' => $connection->username,
                'is_production_stage' => 'on',
            ]);

        $connection->refresh();
        expect($connection->is_production_stage)->toBeTrue();
    });

    it('sets production stage to false when not provided', function (): void {
        $connection = DatabaseConnection::factory()
            ->for($this->user)
            ->mysql()
            ->create([
                'is_production_stage' => true,
            ]);

        $this->actingAs($this->user)
            ->put("/connections/{$connection->id}", [
                'name' => $connection->name,
                'type' => $connection->type->value,
                'host' => $connection->host,
                'port' => $connection->port,
                'database' => $connection->database,
                'username' => $connection->username,
            ]);

        $connection->refresh();
        expect($connection->is_production_stage)->toBeFalse();
    });

    it('prevents updating another users connection', function (): void {
        $otherUser = User::factory()->create();
        $connection = DatabaseConnection::factory()
            ->for($otherUser)
            ->mysql()
            ->create();

        $response = $this->actingAs($this->user)
            ->put("/connections/{$connection->id}", [
                'name' => 'Hacked',
                'type' => 'mysql',
                'host' => 'evil.com',
                'port' => 3306,
                'database' => 'stolen',
                'username' => 'hacker',
            ]);

        $response->assertForbidden();
    });

    it('requires authentication', function (): void {
        $connection = DatabaseConnection::factory()->create();

        $response = $this->put("/connections/{$connection->id}", [
            'name' => 'Test',
            'type' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'test',
            'username' => 'root',
        ]);

        $response->assertRedirect('/login');
    });

    it('validates required fields', function (): void {
        $connection = DatabaseConnection::factory()
            ->for($this->user)
            ->create();

        $response = $this->actingAs($this->user)
            ->put("/connections/{$connection->id}", []);

        $response->assertSessionHasErrors(['name', 'type', 'host', 'port', 'database', 'username']);
    });

    it('validates port range', function (): void {
        $connection = DatabaseConnection::factory()
            ->for($this->user)
            ->create();

        $response = $this->actingAs($this->user)
            ->put("/connections/{$connection->id}", [
                'name' => 'Test',
                'type' => 'mysql',
                'host' => 'localhost',
                'port' => 99999,
                'database' => 'test',
                'username' => 'root',
            ]);

        $response->assertSessionHasErrors(['port']);
    });

    it('validates database type', function (): void {
        $connection = DatabaseConnection::factory()
            ->for($this->user)
            ->create();

        $response = $this->actingAs($this->user)
            ->put("/connections/{$connection->id}", [
                'name' => 'Test',
                'type' => 'invalid_type',
                'host' => 'localhost',
                'port' => 3306,
                'database' => 'test',
                'username' => 'root',
            ]);

        $response->assertSessionHasErrors(['type']);
    });
});
