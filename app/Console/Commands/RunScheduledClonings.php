<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Clonio\ExecuteCloning;
use App\Models\Cloning;
use App\Models\CloningRun;
use Illuminate\Console\Command;

class RunScheduledClonings extends Command
{
    /**
     * @var string
     */
    protected $signature = 'clonings:run-scheduled';

    /**
     * @var string
     */
    protected $description = 'Execute scheduled clonings that are due to run';

    /**
     * Execute the console command.
     */
    public function handle(ExecuteCloning $executeCloning): int
    {
        $dueClonings = Cloning::query()
            ->where('is_scheduled', true)
            ->whereNotNull('schedule')
            ->where(function ($query): void {
                $query->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', now());
            })
            ->get();

        if ($dueClonings->isEmpty()) {
            $this->info('No scheduled clonings due to run.');

            return self::SUCCESS;
        }

        $this->info("Found {$dueClonings->count()} cloning(s) due to run.");

        foreach ($dueClonings as $cloning) {
            $this->executeCloning($cloning, $executeCloning);
        }

        return self::SUCCESS;
    }

    /**
     * Execute a cloning and update its next run time.
     */
    private function executeCloning(Cloning $cloning, ExecuteCloning $executeCloning): void
    {
        $this->info("Executing cloning: {$cloning->title} (ID: {$cloning->id})");

        /** @var CloningRun $run */
        $run = $executeCloning->start($cloning)
            ->log('scheduled_cloning_run_created');

        // Update the next run time
        $nextRunAt = Cloning::calculateNextRunAt($cloning->schedule);
        $cloning->update(['next_run_at' => $nextRunAt]);

        $this->info("  -> Run created (ID: {$run->id}), next run at: " . ($nextRunAt?->format('Y-m-d H:i:s') ?? 'unknown'));
    }
}
