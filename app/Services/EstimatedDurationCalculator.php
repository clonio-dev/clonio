<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cloning;

class EstimatedDurationCalculator
{
    private const float DECAY_FACTOR = 0.8;

    private const int MAX_RUNS = 10;

    /**
     * Calculate weighted average duration from past completed runs.
     *
     * Uses exponential decay weighting: most recent run gets weight 1.0,
     * next gets 0.8, then 0.64, etc.
     *
     * @return int|null Duration in seconds, or null if no completed runs exist
     */
    public function calculate(Cloning $cloning): ?int
    {
        $runs = $cloning->runs()
            ->completed()
            ->whereNotNull('started_at')
            ->whereNotNull('finished_at')
            ->latest('id')
            ->limit(self::MAX_RUNS)
            ->get(['started_at', 'finished_at']);

        if ($runs->isEmpty()) {
            return null;
        }

        $weightedSum = 0.0;
        $totalWeight = 0.0;
        $weight = 1.0;

        foreach ($runs as $run) {
            $duration = (int) ceil($run->started_at->diffInSeconds($run->finished_at));
            $weightedSum += $duration * $weight;
            $totalWeight += $weight;
            $weight *= self::DECAY_FACTOR;
        }

        return (int) round($weightedSum / $totalWeight);
    }
}
