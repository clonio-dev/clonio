<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ConnectionData;
use App\Data\SynchronizeTableSchemaEnum;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\SchemaState;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class CloneSchemaAndPrepareForData implements ShouldBeEncrypted, ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable;

    public int $tries = 2;

    public function __construct(
        public readonly ConnectionData $sourceConnectionData,
        public readonly ConnectionData $targetConnectionData,
        public readonly SynchronizeTableSchemaEnum $synchronizeTableSchemaEnum,
        public readonly bool $keepUnknownTablesOnTarget,
        public readonly ?string $migrationTableName,
    ) {}

    public function handle(DatabaseManager $databaseManager): void
    {
        try {
            /** @var Connection $sourceConnection */
            $sourceConnection = $databaseManager->connectUsing(
                name: $this->sourceConnectionData->connectionName(),
                config: $this->sourceConnectionData->driver->toArray(),
                force: true,
            );

            $sourceSchema = $sourceConnection->getSchemaBuilder();
        } catch (Throwable $exception) {
            Log::error("Failed to connect to database {$this->sourceConnectionData->name}: {$exception->getMessage()}");
            $this->fail($exception);

            return;
        }

        try {
            /** @var Connection $targetConnection */
            $targetConnection = $databaseManager->connectUsing(
                name: $this->targetConnectionData->connectionName(),
                config: $this->targetConnectionData->driver->toArray(),
                force: true,
            );

            $targetSchema = $targetConnection->getSchemaBuilder();
        } catch (Throwable $exception) {
            Log::error("Failed to connect to database {$this->targetConnectionData->name}: {$exception->getMessage()}");
            $this->fail($exception);

            return;
        }

        $this->processUnknownTablesOnTargetWhenNecessary($sourceSchema, $targetSchema);
        $this->truncateTablesOnTargetWhenNecessary($sourceSchema, $targetConnection);
        $this->synchronizeTablesOnTargetWhenNecessary(
            $sourceConnection,
            $sourceSchema,
            $targetConnection,
            $targetSchema,
        );
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return list<class-string>
     */
    public function middleware(): array
    {
        return [new SkipIfBatchCancelled];
    }

    private function processUnknownTablesOnTargetWhenNecessary(Builder $sourceSchema, Builder $targetSchema): void
    {
        if ($this->keepUnknownTablesOnTarget) {
            return;
        }

        $sourceTableNames = $sourceSchema->getTableListing();
        $targetTableNames = $targetSchema->getTableListing();

        $unknownTableNames = array_diff($targetTableNames, $sourceTableNames);
        foreach ($unknownTableNames as $unknownTableName) {
            Log::debug("Dropping table {$unknownTableName} from target database.");
            $targetSchema->drop($unknownTableName);
            Log::info("Dropped table {$unknownTableName} from target database.");
        }
    }

    private function truncateTablesOnTargetWhenNecessary(Builder $sourceSchema, Connection $targetConnection): void
    {
        if ($this->synchronizeTableSchemaEnum !== SynchronizeTableSchemaEnum::TRUNCATE) {
            return;
        }

        $sourceTableNames = $sourceSchema->getTableListing();
        foreach ($sourceTableNames as $tableName) {
            $targetConnection->table($tableName)->delete();
        }
    }

    private function synchronizeTablesOnTargetWhenNecessary(
        Connection $sourceConnection,
        Builder $sourceSchema,
        Connection $targetConnection,
        Builder $targetSchema,
    ): void {
        if ($this->synchronizeTableSchemaEnum !== SynchronizeTableSchemaEnum::DROP_CREATE) {
            return;
        }

        /** @var SchemaState $sourceState */
        $sourceState = $sourceConnection->getSchemaState();
        if ($this->migrationTableName !== null) {
            $sourceState->withMigrationTable($this->migrationTableName);
        }
        $tmpfile = tempnam(sys_get_temp_dir(), 'schema-');
        $sourceState->dump($sourceConnection, $tmpfile);

        if (! is_readable($tmpfile)) {
            @unlink($tmpfile);
            throw new RuntimeException("Failed to create temporary file {$tmpfile}.");
        }

        $schemaDump = file_get_contents($tmpfile);
        if (mb_strlen($schemaDump) === 0) {
            @unlink($tmpfile);
            throw new RuntimeException("Temporary file {$tmpfile} is empty.");
        }

        $sourceTableNames = $sourceSchema->getTableListing();
        foreach ($sourceTableNames as $tableName) {
            $targetSchema->dropIfExists($tableName);
        }

        /** @var SchemaState $targetState */
        $targetState = $targetConnection->getSchemaState();
        $targetState->load($tmpfile);

        @unlink($tmpfile);
    }
}
