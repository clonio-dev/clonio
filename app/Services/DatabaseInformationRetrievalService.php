<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ConnectionData;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Collection;

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

        /** @var ConnectionInterface $connection */
        $connection = $this->connections->get($connectionData->connectionName());

        return $connection;
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
        return $this->getSchema($connectionData)
            ->getTableListing(schemaQualified: $schemaQualified);
    }

    public function withConnectionForTable(ConnectionData $connectionData, string $tableName): TableInformationRetrievalService
    {
        return new TableInformationRetrievalService($this->getConnection($connectionData), $tableName);
    }

    private function establishConnection(ConnectionData $connectionData): ConnectionInterface
    {
        return $this->databaseManager->connectUsing(
            name: $connectionData->connectionName(),
            config: $connectionData->driver->toArray(),
            force: true,
        );
    }
}
