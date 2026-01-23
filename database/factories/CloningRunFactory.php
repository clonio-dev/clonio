<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CloningRunStatus;
use App\Models\Cloning;
use App\Models\CloningRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CloningRun>
 */
class CloningRunFactory extends Factory
{
    protected $model = CloningRun::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'cloning_id' => Cloning::factory(),
            'batch_id' => $this->faker->uuid(),
            'status' => CloningRunStatus::QUEUED->value,
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
            'status' => CloningRunStatus::PROCESSING->value,
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
            'status' => CloningRunStatus::COMPLETED->value,
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
            'status' => CloningRunStatus::FAILED->value,
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
            'status' => CloningRunStatus::CANCELLED->value,
            'finished_at' => now(),
            'current_step' => 1,
        ]);
    }
}
