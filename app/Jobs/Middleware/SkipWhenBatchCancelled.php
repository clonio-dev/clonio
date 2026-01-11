<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Log;

/**
 * @property-read string $tableName
 */
class SkipWhenBatchCancelled
{
    /**
     * Process the job.
     *
     * @param  object  $job
     * @param  callable  $next
     */
    public function handle($job, $next): void
    {
        if (method_exists($job, 'batch') && $job->batch() instanceof Batch && $job->batch()->cancelled()) {
            if (!$job->batch()->hasFailures()) {
                $this->logInfo('table_cancelled', 'Table processing cancelled by user');
            }

            return;
        }

        $next($job);
    }

    private function logInfo(string $event, string $message): void
    {
        if (!isset($this->tableName)) {
            Log::info("[{$event}] {$message}");

            return;
        }

        // In TransferRunLog speichern
        //        $this->run->log($event, [
        //            'table' => $this->tableName,
        //            'message' => $message,
        //            'batch_id' => $this->batch()?->id,
        //        ], 'info');

        // Zusätzlich in Laravel Log
        Log::info("[Table: {$this->tableName}] [{$event}] {$message}");
    }
}
