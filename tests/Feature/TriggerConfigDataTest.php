<?php

declare(strict_types=1);

use App\Data\ApiTriggerConfigData;
use App\Data\TriggerConfigData;
use App\Data\WebhookConfigData;
use App\Models\Cloning;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('TriggerConfigData::from()', function (): void {
    it('returns defaults when config is null', function (): void {
        $data = TriggerConfigData::from(null);

        expect($data->apiTrigger)->toBeInstanceOf(ApiTriggerConfigData::class);
        expect($data->apiTrigger->enabled)->toBeFalse();
        expect($data->webhookOnSuccess)->toBeInstanceOf(WebhookConfigData::class);
        expect($data->webhookOnSuccess->enabled)->toBeFalse();
        expect($data->webhookOnSuccess->url)->toBe('');
        expect($data->webhookOnFailure)->toBeInstanceOf(WebhookConfigData::class);
        expect($data->webhookOnFailure->enabled)->toBeFalse();
    });

    it('parses a full trigger config', function (): void {
        $config = [
            'api_trigger' => ['enabled' => true],
            'webhook_on_success' => [
                'enabled' => true,
                'url' => 'https://example.com/success',
                'method' => 'POST',
                'headers' => ['X-Custom' => 'value'],
                'secret' => 'my-secret',
            ],
            'webhook_on_failure' => [
                'enabled' => false,
                'url' => 'https://example.com/failure',
                'method' => 'PUT',
                'headers' => [],
                'secret' => '',
            ],
        ];

        $data = TriggerConfigData::from($config);

        expect($data->apiTrigger->enabled)->toBeTrue();
        expect($data->webhookOnSuccess->enabled)->toBeTrue();
        expect($data->webhookOnSuccess->url)->toBe('https://example.com/success');
        expect($data->webhookOnSuccess->method)->toBe('POST');
        expect($data->webhookOnSuccess->headers)->toBe(['X-Custom' => 'value']);
        expect($data->webhookOnSuccess->secret)->toBe('my-secret');
        expect($data->webhookOnFailure->enabled)->toBeFalse();
        expect($data->webhookOnFailure->url)->toBe('https://example.com/failure');
        expect($data->webhookOnFailure->method)->toBe('PUT');
    });

    it('handles partial config with missing keys', function (): void {
        $config = [
            'api_trigger' => ['enabled' => true],
        ];

        $data = TriggerConfigData::from($config);

        expect($data->apiTrigger->enabled)->toBeTrue();
        expect($data->webhookOnSuccess->enabled)->toBeFalse();
        expect($data->webhookOnSuccess->url)->toBe('');
        expect($data->webhookOnFailure->enabled)->toBeFalse();
    });

    it('handles empty array config', function (): void {
        $data = TriggerConfigData::from([]);

        expect($data->apiTrigger->enabled)->toBeFalse();
        expect($data->webhookOnSuccess->enabled)->toBeFalse();
        expect($data->webhookOnFailure->enabled)->toBeFalse();
    });
});

describe('TriggerConfigData::toArray()', function (): void {
    it('converts back to array format', function (): void {
        $config = [
            'api_trigger' => ['enabled' => true],
            'webhook_on_success' => [
                'enabled' => true,
                'url' => 'https://example.com/success',
                'method' => 'POST',
                'headers' => [],
                'secret' => 'secret',
            ],
            'webhook_on_failure' => [
                'enabled' => false,
                'url' => '',
                'method' => 'POST',
                'headers' => [],
                'secret' => '',
            ],
        ];

        $data = TriggerConfigData::from($config);
        $array = $data->toArray();

        expect($array)->toBe($config);
    });

    it('round-trips through from() and toArray()', function (): void {
        $original = [
            'api_trigger' => ['enabled' => false],
            'webhook_on_success' => [
                'enabled' => true,
                'url' => 'https://hooks.example.com/notify',
                'method' => 'POST',
                'headers' => ['Authorization' => 'Bearer token123'],
                'secret' => 'hmac-secret',
            ],
            'webhook_on_failure' => [
                'enabled' => true,
                'url' => 'https://hooks.example.com/alert',
                'method' => 'POST',
                'headers' => [],
                'secret' => '',
            ],
        ];

        $result = TriggerConfigData::from($original)->toArray();

        expect($result)->toBe($original);
    });
});

describe('Eloquent cast', function (): void {
    it('casts trigger_config to TriggerConfigData when reading from model', function (): void {
        $cloning = Cloning::factory()->withApiTrigger()->create();

        $cloning->refresh();

        expect($cloning->trigger_config)->toBeInstanceOf(TriggerConfigData::class);
        expect($cloning->trigger_config->apiTrigger->enabled)->toBeTrue();
    });

    it('returns null when trigger_config is null', function (): void {
        $cloning = Cloning::factory()->create(['trigger_config' => null]);

        $cloning->refresh();

        expect($cloning->trigger_config)->toBeNull();
    });

    it('stores array trigger_config correctly', function (): void {
        $config = [
            'api_trigger' => ['enabled' => true],
            'webhook_on_success' => ['enabled' => true, 'url' => 'https://example.com/hook', 'method' => 'POST', 'headers' => [], 'secret' => ''],
            'webhook_on_failure' => ['enabled' => false, 'url' => '', 'method' => 'POST', 'headers' => [], 'secret' => ''],
        ];

        $cloning = Cloning::factory()->create(['trigger_config' => $config]);
        $cloning->refresh();

        expect($cloning->trigger_config)->toBeInstanceOf(TriggerConfigData::class);
        expect($cloning->trigger_config->apiTrigger->enabled)->toBeTrue();
        expect($cloning->trigger_config->webhookOnSuccess->enabled)->toBeTrue();
        expect($cloning->trigger_config->webhookOnSuccess->url)->toBe('https://example.com/hook');
    });

    it('stores TriggerConfigData instance correctly', function (): void {
        $dto = TriggerConfigData::from([
            'api_trigger' => ['enabled' => true],
            'webhook_on_success' => ['enabled' => true, 'url' => 'https://example.com/success', 'method' => 'POST', 'headers' => [], 'secret' => ''],
            'webhook_on_failure' => ['enabled' => false, 'url' => '', 'method' => 'POST', 'headers' => [], 'secret' => ''],
        ]);

        $cloning = Cloning::factory()->create(['trigger_config' => $dto]);
        $cloning->refresh();

        expect($cloning->trigger_config)->toBeInstanceOf(TriggerConfigData::class);
        expect($cloning->trigger_config->apiTrigger->enabled)->toBeTrue();
        expect($cloning->trigger_config->webhookOnSuccess->url)->toBe('https://example.com/success');
    });

    it('preserves webhook config through factory withWebhooks state', function (): void {
        $cloning = Cloning::factory()->withWebhooks('https://example.com/s', 'https://example.com/f')->create();
        $cloning->refresh();

        expect($cloning->trigger_config)->toBeInstanceOf(TriggerConfigData::class);
        expect($cloning->trigger_config->webhookOnSuccess->enabled)->toBeTrue();
        expect($cloning->trigger_config->webhookOnSuccess->url)->toBe('https://example.com/s');
        expect($cloning->trigger_config->webhookOnFailure->enabled)->toBeTrue();
        expect($cloning->trigger_config->webhookOnFailure->url)->toBe('https://example.com/f');
    });
});
