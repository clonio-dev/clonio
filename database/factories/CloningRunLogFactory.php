<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CloningRunLogLevel;
use App\Models\CloningRun;
use App\Models\CloningRunLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CloningRunLog>
 */
class CloningRunLogFactory extends Factory
{
    protected $model = CloningRunLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'run_id' => CloningRun::factory(),
            'level' => CloningRunLogLevel::INFO->value,
            'event_type' => 'cloning_run_created',
            'message' => 'Cloning run created',
            'data' => [],
            'created_at' => now(),
        ];
    }

    /**
     * State: Error log
     */
    public function error(): static
    {
        return $this->state(fn (array $attributes): array => [
            'level' => CloningRunLogLevel::ERROR->value,
            'event_type' => 'table_failed',
            'message' => 'Table processing failed',
        ]);
    }

    /**
     * State: Warning log
     */
    public function warning(): static
    {
        return $this->state(fn (array $attributes): array => [
            'level' => CloningRunLogLevel::WARNING->value,
            'event_type' => 'slow_query',
            'message' => 'Query took longer than expected',
        ]);
    }
}
