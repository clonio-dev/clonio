<?php

declare(strict_types=1);

namespace App\Jobs\Middleware;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Log;

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
            if (! $job->batch()->hasFailures()) {
                $this->logInfo($job, 'table_cancelled', 'Table processing cancelled by user');
            }

            return;
        }

        $next($job);
    }

    private function logInfo(object $job, string $event, string $message): void
    {
        if (! property_exists($job, 'tableName') || $job->tableName === null) {
            Log::info(sprintf('[%s] %s', $event, $message));

            return;
        }

        // In CloningRunLog speichern
        //        $this->run->log($event, [
        //            'table' => $this->tableName,
        //            'message' => $message,
        //            'batch_id' => $this->batch()?->id,
        //        ], 'info');

        // Zusätzlich in Laravel Log
        Log::info(sprintf('[Table: %s] [%s] %s', $job->tableName, $event, $message));
    }
}
