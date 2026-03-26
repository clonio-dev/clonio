<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\LogsProcessSteps;
use App\Jobs\Concerns\TransferBatchJob;
use App\Models\CloningRun;
use App\Services\KeyMappingRepository;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

class CleanupKeyMappingJob implements ShouldBeEncrypted, ShouldQueue
{
    use Batchable;
    use InteractsWithQueue;
    use LogsProcessSteps;
    use Queueable;
    use TransferBatchJob;

    public int $tries = 3;

    public function __construct(
        public readonly CloningRun $run,
    ) {}

    public function handle(KeyMappingRepository $mappingRepository): void
    {
        try {
            $mappingRepository->deleteByRun($this->run);

            $this->logInfo('mapping_cleaned', 'Key mapping records deleted', [
                'run_id' => $this->run->id,
            ]);
        } catch (Throwable $throwable) {
            // Ensure cleanup always succeeds — log error but don't fail the run
            $this->logWarning('mapping_clean_failed', 'Failed to delete key mapping records: ' . $throwable->getMessage(), [
                'run_id' => $this->run->id,
            ]);
        }
    }
}
