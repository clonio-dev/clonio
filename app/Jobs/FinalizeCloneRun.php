<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CloningRunStatus;
use App\Models\CloningRun;
use App\Services\AuditService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class FinalizeCloneRun implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $batchId, public readonly int $runId) {}

    public function handle(AuditService $auditService): void
    {
        $batch = Bus::findBatch($this->batchId);
        $run = CloningRun::query()->find($this->runId);

        if (! $batch || ! $run) {
            Log::error('Could not find batch or run for finalize job');

            return;
        }

        if ($batch->cancelled()) {
            $run->update(['status' => CloningRunStatus::CANCELLED, 'finished_at' => now()]);

            return;
        }

        if ($batch->hasFailures()) {
            $run->update(['status' => CloningRunStatus::FAILED, 'finished_at' => now()]);

            return;
        }

        if ($batch->finished()) {
            $run->update(['status' => CloningRunStatus::COMPLETED, 'finished_at' => now()]);

            try {
                $auditService->signRun($run);
            } catch (Exception) {
            }
        }
    }
}
