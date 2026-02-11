<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CloningRunLogLevel;
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

        if ($batch->hasFailures()) {
            $run->update(['status' => CloningRunStatus::FAILED, 'finished_at' => now()]);

            if ($run->cloning) {
                $wasPaused = $run->cloning->recordFailure();

                if ($wasPaused) {
                    $run->log('auto_paused', [
                        'message' => 'Cloning auto-paused after 3 consecutive failures',
                        'consecutive_failures' => $run->cloning->consecutive_failures,
                        'level' => CloningRunLogLevel::WARNING,
                    ]);
                }

                $this->dispatchWebhook($run, 'failure');
            }

            return;
        }

        if ($batch->cancelled()) {
            $run->update(['status' => CloningRunStatus::CANCELLED, 'finished_at' => now()]);

            return;
        }

        if ($batch->finished()) {
            $run->update(['status' => CloningRunStatus::COMPLETED, 'finished_at' => now()]);

            $run->cloning?->recordSuccess();

            try {
                $auditService->signRun($run);
                $run->updateQuietly(['public_token' => bin2hex(random_bytes(32))]);
            } catch (Exception) {
            }

            $this->dispatchWebhook($run, 'success');
        }
    }

    private function dispatchWebhook(CloningRun $run, string $event): void
    {
        $triggerConfig = $run->cloning?->trigger_config;

        if (! $triggerConfig) {
            return;
        }

        $webhookKey = $event === 'success' ? 'webhook_on_success' : 'webhook_on_failure';
        $webhookConfig = $triggerConfig[$webhookKey] ?? null;

        if (! $webhookConfig || ! ($webhookConfig['enabled'] ?? false) || empty($webhookConfig['url'])) {
            return;
        }

        dispatch(new DispatchWebhook($run, $webhookConfig, $event));
    }
}
