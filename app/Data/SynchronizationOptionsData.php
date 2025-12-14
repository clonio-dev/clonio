<?php

declare(strict_types=1);

namespace App\Data;

final readonly class SynchronizationOptionsData
{
    public function __construct(
        public bool $disableForeignKeyConstraints = true,
        public SynchronizeTableSchemaEnum $synchronizeTableSchema = SynchronizeTableSchemaEnum::DROP_CREATE,
        public bool $keepUnknownTablesOnTarget = true,
        public ?string $migrationTableName = null,
        public int $chunkSize = 1000,
    ) {}
}
