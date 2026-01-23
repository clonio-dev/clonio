<?php

namespace App\Jobs;

use App\Models\DatabaseConnection;
use App\Services\DatabaseInformationRetrievalService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TestConnection implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly DatabaseConnection $databaseConnection)
    {}

    public function handle(DatabaseInformationRetrievalService $databaseInformationRetrievalService): void
    {
        try {
            $connectionData = $this->databaseConnection->toConnectionDataDto();

            $databaseInformationRetrievalService->getConnection($connectionData);

            $this->databaseConnection->markConnected();
        } catch (\RuntimeException $exception) {
            // PDO driver exception
            $this->databaseConnection->markNotConnected('Connection Failed');
        } catch (\Exception $exception) {
            // driver missing for connection data DTO
        }
    }
}
