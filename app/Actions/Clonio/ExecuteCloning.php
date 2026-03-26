<?php

declare(strict_types=1);

namespace App\Actions\Clonio;

use App\Data\SynchronizationOptionsData;
use App\Enums\CloningRunStatus;
use App\Jobs\CleanupKeyMappingJob;
use App\Jobs\FinalizeCloneRun;
use App\Jobs\SynchronizeDatabase;
use App\Jobs\TestConnection;
use App\Models\Cloning;
use App\Models\CloningRun;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

class ExecuteCloning
{
    public function start(Cloning $cloning): CloningRun
    {
        $cloning->loadMissing('sourceConnection', 'targetConnection');

        /** @var CloningRun $run */
        $run = CloningRun::query()->create([
            'user_id' => $cloning->user_id,
            'cloning_id' => $cloning->id,
            'batch_id' => null,
            'status' => CloningRunStatus::QUEUED,
            'started_at' => null,
        ]);

        $run->log('cloning_run_created');

        $connectionDataSource = $cloning->sourceConnection->toConnectionDataDto();
        $connectionDataTarget = $cloning->targetConnection->toConnectionDataDto();
        $options = SynchronizationOptionsData::from($cloning->anonymization_config);

        Bus::batch([
            new TestConnection($cloning->sourceConnection, $run),
            new TestConnection($cloning->targetConnection, $run),
            new SynchronizeDatabase(
                options: $options,
                sourceConnectionData: $connectionDataSource,
                targetConnectionData: $connectionDataTarget,
                run: $run,
            ),
        ])
            ->name('Synchronize database ' . $connectionDataSource->name)
            ->before(function (Batch $batch) use ($run): void {
                $run->update(['batch_id' => $batch->id, 'started_at' => now()]);
            })
            ->then(function (Batch $batch): void {
                //
            })
            ->progress(function (Batch $batch) use ($run): void {
                $run->update([
                    'status' => CloningRunStatus::PROCESSING,
                    'progress_percent' => $batch->progress(),
                    'current_step' => $batch->processedJobs(),
                    'total_steps' => $batch->totalJobs,
                ]);
            })
            ->finally(function (Batch $batch) use ($run, $options): void {
                $finalize = new FinalizeCloneRun($batch->id, $run->id);

                if ($options->keyRemapping?->enabled) {
                    Bus::chain([new CleanupKeyMappingJob($run), $finalize])->dispatch();
                } else {
                    Bus::dispatch($finalize);
                }
            })
            ->dispatch();

        return $run;
    }
}
