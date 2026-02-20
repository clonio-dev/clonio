<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use Illuminate\Database\QueryException;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * @property-read string $tableName
 */
trait HandlesExceptions
{
    use LogsProcessSteps;

    /**
     * @throws RuntimeException
     */
    private function handleQueryException(QueryException $e): void
    {
        // Spezifische Fehler wurden bereits in den Methoden gehandled
        // Hier nur noch unbekannte DB-Fehler
        $this->logError(
            'database_error',
            'Database error: ' . $e->getMessage()
        );

        $tableName = property_exists($this, 'tableName') ? $this->tableName : 'unknown';

        throw new RuntimeException(
            sprintf('Database operation failed for table %s. ', $tableName) .
            ('Error: ' . $e->getMessage()),
            previous: $e
        );
    }

    /**
     * @throws RuntimeException
     */
    private function handleConnectionException(PDOException $e): void
    {
        $this->logError(
            'connection_lost',
            'Database connection lost: ' . $e->getMessage()
        );

        $tableName = property_exists($this, 'tableName') ? $this->tableName : 'unknown';

        throw new RuntimeException(
            sprintf('Database connection was lost during processing of table %s. ', $tableName) .
            'This might be due to network issues or the database server restarting. ' .
            'Please retry the operation.',
            previous: $e
        );
    }

    /**
     * @throws RuntimeException
     */
    private function handleUnexpectedException(Throwable $e): void
    {
        $this->logError(
            'unexpected_error',
            'Unexpected error: ' . $e->getMessage()
        );

        $tableName = property_exists($this, 'tableName') ? $this->tableName : 'unknown';

        throw new RuntimeException(
            sprintf('An unexpected error occurred while processing table %s: ', $tableName) .
            $e->getMessage(),
            previous: $e
        );
    }
}
