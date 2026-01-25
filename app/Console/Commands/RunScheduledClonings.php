<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Data\SynchronizationOptionsData;
use App\Enums\CloningRunStatus;
use App\Jobs\SynchronizeDatabase;
use App\Jobs\TestConnection;
use App\Models\Cloning;
use App\Models\CloningRun;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

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
    public function handle(): int
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
            $this->executeCloning($cloning);
        }

        return self::SUCCESS;
    }

    /**
     * Execute a cloning and update its next run time.
     */
    private function executeCloning(Cloning $cloning): void
    {
        $this->info("Executing cloning: {$cloning->title} (ID: {$cloning->id})");

        /** @var CloningRun $run */
        $run = CloningRun::query()->create([
            'user_id' => $cloning->user_id,
            'cloning_id' => $cloning->id,
            'batch_id' => null,
            'status' => CloningRunStatus::QUEUED,
            'started_at' => null,
        ]);

        $run->log('scheduled_cloning_run_created');

        $connectionDataSource = $cloning->sourceConnection->toConnectionDataDto();
        $connectionDataTarget = $cloning->targetConnection->toConnectionDataDto();

        Bus::batch([
            new TestConnection($cloning->sourceConnection, $run),
            new TestConnection($cloning->targetConnection, $run),
            new SynchronizeDatabase(
                options: SynchronizationOptionsData::from($cloning->anonymization_config),
                sourceConnectionData: $connectionDataSource,
                targetConnectionData: $connectionDataTarget,
                run: $run,
            ),
        ])
            ->name('Scheduled sync: ' . $cloning->title)
            ->before(function (Batch $batch) use ($run): void {
                $run->update(['batch_id' => $batch->id, 'started_at' => now()]);
            })
            ->then(function (Batch $batch) use ($run): void {
                $run->update(['status' => CloningRunStatus::COMPLETED, 'finished_at' => now()]);
            })
            ->progress(function (Batch $batch) use ($run): void {
                $run->update([
                    'status' => CloningRunStatus::PROCESSING,
                    'progress_percent' => $batch->progress(),
                    'current_step' => $batch->processedJobs(),
                    'total_steps' => $batch->totalJobs,
                ]);
            })
            ->finally(function (Batch $batch) use ($run): void {
                if ($batch->cancelled()) {
                    $run->update(['status' => CloningRunStatus::CANCELLED]);
                }
                if ($batch->hasFailures()) {
                    $run->update(['status' => CloningRunStatus::FAILED, 'finished_at' => now()]);
                }
            })
            ->dispatch();

        // Update the next run time
        $nextRunAt = Cloning::calculateNextRunAt($cloning->schedule);
        $cloning->update(['next_run_at' => $nextRunAt]);

        $this->info("  -> Run created (ID: {$run->id}), next run at: " . ($nextRunAt?->format('Y-m-d H:i:s') ?? 'unknown'));
    }
}
