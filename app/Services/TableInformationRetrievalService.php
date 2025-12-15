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
        $allIndexes = $this->connection->getSchemaBuilder()->getIndexes($this->tableName);
        $orderColumns = collect($allIndexes)
            ->firstWhere('primary', true)['columns'] ?? [];

        if (count($orderColumns) > 0) {
            return $orderColumns;
        }

        return [$this->connection->getSchemaBuilder()->getColumnListing($this->tableName)[0]];
    }
}
