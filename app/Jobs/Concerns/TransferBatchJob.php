<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use App\Jobs\Middleware\SkipWhenBatchCancelled;
use Illuminate\Bus\Batchable;

trait TransferBatchJob
{
    use Batchable;

    public int $timeout = 3600; // 1 Stunde

    public int $maxExceptions = 3;

    /**
     * Retry-Delays für temporäre Fehler (Sekunden)
     */
    public int $backoff = 30;

    /**
     * Get the middleware the job should pass through.
     *
     * @return array{SkipWhenBatchCancelled}
     */
    public function middleware(): array
    {
        return [new SkipWhenBatchCancelled()];
    }
}
