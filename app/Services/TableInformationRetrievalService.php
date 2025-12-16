<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

final readonly class TableInformationRetrievalService
{
    public function __construct(
        private ConnectionInterface|Connection $connection,
        private string $tableName,
    ) {}

    public function recordCount(): int
    {
        return $this->query()->count();
    }

    public function query(): Builder
    {
        return $this->connection
            ->table($this->tableName);
    }

    /**
     * @return list<string>
     */
    public function orderColumns(): array
    {
        assert($this->connection instanceof Connection);

        $allIndexes = $this->connection->getSchemaBuilder()->getIndexes($this->tableName);
        $primaryIndex = collect($allIndexes)->firstWhere('primary', true);

        /** @var list<string> $orderColumns */
        $orderColumns = is_array($primaryIndex) ? $primaryIndex['columns'] : [];

        if (count($orderColumns) > 0) {
            return $orderColumns;
        }

        $columns = $this->connection->getSchemaBuilder()->getColumnListing($this->tableName);

        return [$columns[0]];
    }
}
