<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\Log;
use Throwable;

class DisableForeignKeyConstraintsOnSchema implements ShouldBeEncrypted, ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public function __construct(
        public readonly ConnectionData $connectionData,
    ) {}

    public function handle(
        DatabaseInformationRetrievalService $dbInformationRetrievalService,
    ): void {
        try {
            Log::debug('Disabling foreign key constraints on target schema ' . $this->connectionData->name);

            $dbInformationRetrievalService
                ->getSchema($this->connectionData)
                ->disableForeignKeyConstraints();
        } catch (Throwable $exception) {
            Log::error('Failed to disable foreign key constraints on target schema ' . $this->connectionData->name);
            $this->fail($exception);
        }
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return list<class-string>
     */
    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }
}
