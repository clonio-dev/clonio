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
            $mappingRepository->dropTable($this->run);

            $this->logInfo('mapping_table_deleted', 'ID mapping table deleted', [
                'mapping_table' => $mappingRepository->tableName($this->run),
                'status' => 'success',
            ]);
        } catch (Throwable $e) {
            // Ensure cleanup always succeeds — log error but don't fail the run
            $this->logWarning('mapping_table_delete_failed', 'Failed to delete ID mapping table: ' . $e->getMessage(), [
                'mapping_table' => $mappingRepository->tableName($this->run),
            ]);
        }
    }
}
