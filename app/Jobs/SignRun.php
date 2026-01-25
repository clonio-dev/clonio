<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\LogsProcessSteps;
use App\Jobs\Concerns\TransferBatchJob;
use App\Models\CloningRun;
use App\Services\AuditService;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SignRun implements ShouldQueue
{
    use Batchable, LogsProcessSteps, Queueable, TransferBatchJob;

    public int $tries = 1;

    public string $tableName = '';

    public function __construct(private readonly ?CloningRun $run = null) {}

    public function handle(AuditService $auditService): void
    {
        try {
            $auditService->signRun($this->run);
        } catch (Exception) {
        }
    }
}
