<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<TriggerConfigData, TriggerConfigData>
 */
final readonly class TriggerConfigData implements CastsAttributes
{
    public function __construct(
        public ApiTriggerConfigData $apiTrigger = new ApiTriggerConfigData(),
        public WebhookConfigData $webhookOnSuccess = new WebhookConfigData(),
        public WebhookConfigData $webhookOnFailure = new WebhookConfigData(),
    ) {}

    /**
     * Build TriggerConfigData from the stored trigger config.
     *
     * @param  array{api_trigger?: array{enabled?: bool}, webhook_on_success?: array{enabled?: bool, url?: string, method?: string, headers?: array<string, string>, secret?: string}, webhook_on_failure?: array{enabled?: bool, url?: string, method?: string, headers?: array<string, string>, secret?: string}}|null  $config
     */
    public static function from(?array $config): self
    {
        if (! $config) {
            return new self();
        }

        $apiTrigger = new ApiTriggerConfigData(
            enabled: $config['api_trigger']['enabled'] ?? false,
        );

        $webhookOnSuccess = self::buildWebhookConfig($config['webhook_on_success'] ?? []);
        $webhookOnFailure = self::buildWebhookConfig($config['webhook_on_failure'] ?? []);

        return new self(
            apiTrigger: $apiTrigger,
            webhookOnSuccess: $webhookOnSuccess,
            webhookOnFailure: $webhookOnFailure,
        );
    }

    /**
     * Convert the DTO back to an array for storage.
     *
     * @return array{api_trigger: array{enabled: bool}, webhook_on_success: array{enabled: bool, url: string, method: string, headers: array<string, string>, secret: string}, webhook_on_failure: array{enabled: bool, url: string, method: string, headers: array<string, string>, secret: string}}
     */
    public function toArray(): array
    {
        return [
            'api_trigger' => [
                'enabled' => $this->apiTrigger->enabled,
            ],
            'webhook_on_success' => [
                'enabled' => $this->webhookOnSuccess->enabled,
                'url' => $this->webhookOnSuccess->url,
                'method' => $this->webhookOnSuccess->method,
                'headers' => $this->webhookOnSuccess->headers,
                'secret' => $this->webhookOnSuccess->secret,
            ],
            'webhook_on_failure' => [
                'enabled' => $this->webhookOnFailure->enabled,
                'url' => $this->webhookOnFailure->url,
                'method' => $this->webhookOnFailure->method,
                'headers' => $this->webhookOnFailure->headers,
                'secret' => $this->webhookOnFailure->secret,
            ],
        ];
    }

    /**
     * Cast the given value (from database JSON) to TriggerConfigData.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?self
    {
        if ($value === null) {
            return null;
        }

        $decoded = is_string($value) ? json_decode($value, true) : $value;

        return self::from($decoded);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof self) {
            return json_encode($value->toArray());
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }

    /**
     * @param  array{enabled?: bool, url?: string, method?: string, headers?: array<string, string>, secret?: string}  $config
     */
    private static function buildWebhookConfig(array $config): WebhookConfigData
    {
        return new WebhookConfigData(
            enabled: $config['enabled'] ?? false,
            url: $config['url'] ?? '',
            method: $config['method'] ?? 'POST',
            headers: $config['headers'] ?? [],
            secret: $config['secret'] ?? '',
        );
    }
}
