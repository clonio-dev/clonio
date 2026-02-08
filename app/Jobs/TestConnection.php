<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\LogsProcessSteps;
use App\Jobs\Concerns\TransferBatchJob;
use App\Models\CloningRun;
use App\Models\DatabaseConnection;
use App\Services\DatabaseInformationRetrievalService;
use App\Services\SchemaInspector\SchemaInspectorFactory;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TestConnection implements ShouldQueue
{
    use Batchable, LogsProcessSteps, Queueable, TransferBatchJob;

    public int $tries = 2;

    public string $tableName = '';

    public function __construct(private readonly DatabaseConnection $databaseConnection, private readonly ?CloningRun $run = null) {}

    public function handle(DatabaseInformationRetrievalService $databaseInformationRetrievalService): void
    {
        try {
            $connectionData = $this->databaseConnection->toConnectionDataDto();

            $connection = $databaseInformationRetrievalService->getConnection($connectionData);

            // Get the DBMS version from database metadata
            $dbmsVersion = $this->getDbmsVersion($connection);

            $this->databaseConnection->markConnected('Healthy', $dbmsVersion);

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

    /**
     * Get the DBMS version from the database connection.
     */
    private function getDbmsVersion(mixed $connection): ?string
    {
        if (! $connection instanceof Connection) {
            return null;
        }

        try {
            $inspector = SchemaInspectorFactory::create($connection);
            $metadata = $inspector->getDatabaseMetadata($connection);

            return $metadata['version'] ?? null;
        } catch (Exception $e) {
            // If we can't get the version, that's okay - connection still works
            Log::warning('Could not retrieve DBMS version: ' . $e->getMessage());

            return null;
        }
    }
}
