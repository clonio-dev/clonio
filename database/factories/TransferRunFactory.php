<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TransferRunStatus;
use App\Models\DatabaseConnection;
use App\Models\TransferRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransferRun>
 */
class TransferRunFactory extends Factory
{
    protected $model = TransferRun::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source_connection_id' => DatabaseConnection::factory()->sqlite(),
            'target_connection_id' => DatabaseConnection::factory()->sqlite()->testDatabase(),
            'script' => null,
            'batch_id' => $this->faker->uuid(),
            'status' => TransferRunStatus::QUEUED->value,
            'started_at' => now(),
            'finished_at' => null,
            'current_step' => 0,
            'total_steps' => 3,
            'progress_percent' => 0,
            'error_message' => null,
        ];
    }

    /**
     * State: Running
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TransferRunStatus::PROCESSING->value,
            'current_step' => 1,
            'progress_percent' => 33,
        ]);
    }

    /**
     * State: Completed
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TransferRunStatus::COMPLETED->value,
            'finished_at' => now(),
            'current_step' => 3,
            'total_steps' => 3,
            'progress_percent' => 100,
        ]);
    }

    /**
     * State: Failed
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TransferRunStatus::FAILED->value,
            'finished_at' => now(),
            'current_step' => 1,
            'error_message' => 'Connection timeout',
        ]);
    }

    /**
     * State: Cancelled
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TransferRunStatus::CANCELLED->value,
            'finished_at' => now(),
            'current_step' => 1,
        ]);
    }
}
