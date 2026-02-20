<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cloning;
use App\Models\DatabaseConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cloning>
 */
class CloningFactory extends Factory
{
    protected $model = Cloning::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->words(3, true),
            'source_connection_id' => DatabaseConnection::factory()->sqlite(),
            'target_connection_id' => DatabaseConnection::factory()->sqlite()->testDatabase(),
            'anonymization_config' => null,
            'schedule' => null,
            'trigger_config' => null,
            'api_trigger_token' => null,
            'is_scheduled' => false,
            'is_paused' => false,
            'consecutive_failures' => 0,
            'next_run_at' => null,
        ];
    }

    /**
     * State: With anonymization config
     */
    public function withAnonymization(): static
    {
        return $this->state(fn (array $attributes): array => [
            'anonymization_config' => [
                'tables' => [
                    [
                        'tableName' => 'users',
                        'columnMutations' => [
                            [
                                'columnName' => 'email',
                                'strategy' => 'mask',
                                'options' => ['visibleChars' => 2, 'maskChar' => '*'],
                            ],
                        ],
                    ],
                ],
                'version' => '1.0',
            ],
        ]);
    }

    /**
     * State: Scheduled
     */
    public function scheduled(string $schedule = '0 0 * * *'): static
    {
        return $this->state(fn (array $attributes): array => [
            'schedule' => $schedule,
            'is_scheduled' => true,
            'next_run_at' => now()->addDay(),
        ]);
    }

    /**
     * State: Paused
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_paused' => true,
        ]);
    }

    /**
     * State: With API trigger enabled
     */
    public function withApiTrigger(): static
    {
        return $this->state(fn (array $attributes): array => [
            'trigger_config' => [
                'webhook_on_success' => ['enabled' => false, 'url' => '', 'method' => 'POST', 'headers' => [], 'secret' => ''],
                'webhook_on_failure' => ['enabled' => false, 'url' => '', 'method' => 'POST', 'headers' => [], 'secret' => ''],
                'api_trigger' => ['enabled' => true],
            ],
            'api_trigger_token' => bin2hex(random_bytes(32)),
        ]);
    }

    /**
     * State: With webhooks enabled
     */
    public function withWebhooks(string $successUrl = 'https://example.com/success', string $failureUrl = 'https://example.com/failure'): static
    {
        return $this->state(fn (array $attributes): array => [
            'trigger_config' => [
                'webhook_on_success' => ['enabled' => true, 'url' => $successUrl, 'method' => 'POST', 'headers' => [], 'secret' => ''],
                'webhook_on_failure' => ['enabled' => true, 'url' => $failureUrl, 'method' => 'POST', 'headers' => [], 'secret' => ''],
                'api_trigger' => ['enabled' => false],
            ],
        ]);
    }

    /**
     * State: With consecutive failures
     */
    public function withConsecutiveFailures(int $count): static
    {
        return $this->state(fn (array $attributes): array => [
            'consecutive_failures' => $count,
        ]);
    }
}
