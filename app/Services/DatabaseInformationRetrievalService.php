<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ConnectionData;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

final readonly class DatabaseInformationRetrievalService
{
    /** @var Collection<string, ConnectionInterface> */
    private Collection $connections;

    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
        $this->connections = new Collection();
    }

    public function getConnection(ConnectionData $connectionData): ConnectionInterface
    {
        if (! $this->connections->has($connectionData->connectionName())) {
            $this->connections->put($connectionData->connectionName(), $this->establishConnection($connectionData));
        }

        return $this->connections->get($connectionData->connectionName());
    }

    public function getSchema(ConnectionData $connectionData): Builder
    {
        return $this->getConnection($connectionData)
            ->getSchemaBuilder();
    }

    /**
     * @return list<string>
     */
    public function getTableNames(ConnectionData $connectionData, bool $schemaQualified = false): array
    {
        return $this->getSchema($connectionData)
            ->getTableListing(schemaQualified: $schemaQualified);
    }

    public function withConnectionForTable(ConnectionData $connectionData, string $tableName): TableInformationRetrievalService
    {
        return new TableInformationRetrievalService($this->getConnection($connectionData), $tableName);
    }

    /**
     * @throws RuntimeException
     */
    private function establishConnection(ConnectionData $connectionData): ConnectionInterface
    {
        try {
            return $this->databaseManager->connectUsing(
                name: $connectionData->connectionName(),
                config: $connectionData->driver->toArray(),
                force: true,
            );
        } catch (Throwable $exception) {
            throw new RuntimeException("Failed to connect to database {$connectionData->name}: {$exception->getMessage()}", $exception->getCode(), $exception);
        }
    }
}
