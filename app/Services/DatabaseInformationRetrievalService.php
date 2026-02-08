<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ConnectionData;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Collection;
use PDOException;
use RuntimeException;

final readonly class DatabaseInformationRetrievalService
{
    /** @var Collection<string, ConnectionInterface> */
    private Collection $connections;

    private Collection $connectionMap;

    public function __construct(
        private DatabaseManager $databaseManager,
    ) {
        $this->connections = new Collection();
        $this->connectionMap = new Collection();
    }

    public function getConnection(ConnectionData $connectionData): ConnectionInterface
    {
        if (! $this->connections->has($connectionData->connectionName)) {
            $this->connections->put($connectionData->connectionName, $this->establishConnection($connectionData));
        }

        /** @var ConnectionInterface $connection */
        $connection = $this->connections->get($connectionData->connectionName);

        return $connection;
    }

    public function connectionMap(): Collection
    {
        return $this->connectionMap;
    }

    public function getSchema(ConnectionData $connectionData): Builder
    {
        $connection = $this->getConnection($connectionData);
        assert($connection instanceof \Illuminate\Database\Connection);

        return $connection->getSchemaBuilder();
    }

    /**
     * @return list<string>
     */
    public function getTableNames(ConnectionData $connectionData, bool $schemaQualified = false): array
    {
        $schema = $this->getSchema($connectionData);

        return $schema->getTableListing($schema->getCurrentSchemaName(), schemaQualified: $schemaQualified);
    }

    public function withConnectionForTable(ConnectionData $connectionData, string $tableName): TableInformationRetrievalService
    {
        return new TableInformationRetrievalService($this->getConnection($connectionData), $tableName);
    }

    /**
     * @throws RuntimeException when PDO connection failed
     */
    private function establishConnection(ConnectionData $connectionData): ConnectionInterface
    {
        try {
            $connection = $this->databaseManager->connectUsing(
                name: $connectionData->connectionName,
                config: $connectionData->driver->toArray(),
                force: true,
            );

            // Test connection
            $connection->getPdo();

            $this->connectionMap->put($connectionData->connectionName, $connectionData->name);

            return $connection;
        } catch (PDOException $e) {
            //            $this->logError(
            //                "connection_{$connectionData->connectionName}_failed",
            //                "Failed to connect to {$connectionData->connectionName} database: {$e->getMessage()}"
            //            );

            throw new RuntimeException("Could not connect to {$connectionData->connectionName} database. " .
                'Please check credentials and network connectivity.', $e->getCode(), previous: $e);
        }
    }
}
