<?php

declare(strict_types=1);

use App\Actions\Clonio\ExecuteCloning;
use App\Data\TriggerConfigData;
use App\Models\Cloning;
use App\Models\DatabaseConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

describe('API trigger endpoint', function (): void {
    it('triggers a cloning run with a valid token', function (): void {
        $cloning = Cloning::factory()
            ->for($this->user)
            ->withApiTrigger()
            ->create();

        $this->mock(ExecuteCloning::class, function ($mock) use ($cloning): void {
            $mock->shouldReceive('start')
                ->once()
                ->andReturn($cloning->runs()->create([
                    'user_id' => $cloning->user_id,
                    'status' => 'queued',
                ]));
        });

        $response = $this->postJson('/api/trigger/' . $cloning->api_trigger_token);

        $response->assertStatus(202);
        $response->assertJson(['message' => 'Cloning execution started.']);
    });

    it('logs api_triggered event when triggered via API', function (): void {
        $cloning = Cloning::factory()
            ->for($this->user)
            ->withApiTrigger()
            ->create();

        $this->mock(ExecuteCloning::class, function ($mock) use ($cloning): void {
            $mock->shouldReceive('start')
                ->once()
                ->andReturn($cloning->runs()->create([
                    'user_id' => $cloning->user_id,
                    'status' => 'queued',
                ]));
        });

        $this->postJson('/api/trigger/' . $cloning->api_trigger_token);

        $run = $cloning->runs()->first();

        expect($run->logs()->where('event_type', 'api_triggered')->exists())->toBeTrue();
        expect($run->logs()->where('event_type', 'api_triggered')->first()->message)
            ->toBe('Cloning triggered via API');
    });

    it('returns 404 for invalid token', function (): void {
        $response = $this->postJson('/api/trigger/invalid-token-that-does-not-exist');

        $response->assertNotFound();
    });

    it('returns 403 when API trigger is disabled', function (): void {
        $cloning = Cloning::factory()
            ->for($this->user)
            ->create([
                'trigger_config' => [
                    'webhook_on_success' => ['enabled' => false],
                    'webhook_on_failure' => ['enabled' => false],
                    'api_trigger' => ['enabled' => false],
                ],
                'api_trigger_token' => bin2hex(random_bytes(32)),
            ]);

        $response = $this->postJson('/api/trigger/' . $cloning->api_trigger_token);

        $response->assertForbidden();
    });
});

describe('trigger config storage', function (): void {
    it('stores trigger config when creating a cloning', function (): void {
        $sourceConnection = DatabaseConnection::factory()
            ->for($this->user)
            ->sqlite()
            ->create();

        $targetConnection = DatabaseConnection::factory()
            ->for($this->user)
            ->sqlite()
            ->testDatabase()
            ->create();

        $triggerConfig = [
            'webhook_on_success' => ['enabled' => true, 'url' => 'https://example.com/success', 'method' => 'POST', 'headers' => [], 'secret' => ''],
            'webhook_on_failure' => ['enabled' => false, 'url' => '', 'method' => 'POST', 'headers' => [], 'secret' => ''],
            'api_trigger' => ['enabled' => true],
        ];

        $response = $this->actingAs($this->user)
            ->post('/clonings', [
                'title' => 'Test Cloning',
                'source_connection_id' => $sourceConnection->id,
                'target_connection_id' => $targetConnection->id,
                'anonymization_config' => null,
                'execute_now' => '0',
                'is_scheduled' => '0',
                'schedule' => '',
                'trigger_config' => json_encode($triggerConfig),
            ]);

        $response->assertRedirect();

        $cloning = Cloning::query()->latest('id')->first();
        expect($cloning->trigger_config)->toBeInstanceOf(TriggerConfigData::class);
        expect($cloning->trigger_config->webhookOnSuccess->enabled)->toBeTrue();
        expect($cloning->trigger_config->apiTrigger->enabled)->toBeTrue();
        expect($cloning->api_trigger_token)->not->toBeNull();
        expect(mb_strlen((string) $cloning->api_trigger_token))->toBe(64);
    });

    it('generates API trigger token only when API trigger is enabled', function (): void {
        $sourceConnection = DatabaseConnection::factory()
            ->for($this->user)
            ->sqlite()
            ->create();

        $targetConnection = DatabaseConnection::factory()
            ->for($this->user)
            ->sqlite()
            ->testDatabase()
            ->create();

        $triggerConfig = [
            'webhook_on_success' => ['enabled' => true, 'url' => 'https://example.com/success', 'method' => 'POST', 'headers' => [], 'secret' => ''],
            'webhook_on_failure' => ['enabled' => false, 'url' => '', 'method' => 'POST', 'headers' => [], 'secret' => ''],
            'api_trigger' => ['enabled' => false],
        ];

        $this->actingAs($this->user)
            ->post('/clonings', [
                'title' => 'Test Cloning',
                'source_connection_id' => $sourceConnection->id,
                'target_connection_id' => $targetConnection->id,
                'anonymization_config' => null,
                'execute_now' => '0',
                'is_scheduled' => '0',
                'schedule' => '',
                'trigger_config' => json_encode($triggerConfig),
            ]);

        $cloning = Cloning::query()->latest('id')->first();
        expect($cloning->api_trigger_token)->toBeNull();
    });
});
