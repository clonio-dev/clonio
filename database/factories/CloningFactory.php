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
            'is_scheduled' => false,
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
}
