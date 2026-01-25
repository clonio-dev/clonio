<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\LogsProcessSteps;
use App\Jobs\Concerns\TransferBatchJob;
use App\Models\CloningRun;
use App\Models\DatabaseConnection;
use App\Services\DatabaseInformationRetrievalService;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TestConnection implements ShouldQueue
{
    use Batchable, Queueable, LogsProcessSteps, TransferBatchJob;

    public int $tries = 2;

    public string $tableName = '';

    public function __construct(private readonly DatabaseConnection $databaseConnection, private readonly ?CloningRun $run = null) {}

    public function handle(DatabaseInformationRetrievalService $databaseInformationRetrievalService): void
    {
        try {
            $connectionData = $this->databaseConnection->toConnectionDataDto();

            $databaseInformationRetrievalService->getConnection($connectionData);

            $this->databaseConnection->markConnected();

            $this->logSuccess('connection_tested', 'Connection successful to ' . $this->databaseConnection->name);
        } catch (RuntimeException $exception) {
            // PDO driver exception
            $this->databaseConnection->markNotConnected('Connection Failed');

            $this->logError('connection_failed', $exception->getMessage());
            $this->logErrorMessage($exception->getMessage(), $databaseInformationRetrievalService->connectionMap());
        } catch (Exception $exception) {
            // driver missing for connection data DTO
            $this->logError('connection_failed', $exception->getMessage());
            $this->logErrorMessage($exception->getMessage(), $databaseInformationRetrievalService->connectionMap());
        }
    }
}
