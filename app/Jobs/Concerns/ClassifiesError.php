<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use Illuminate\Database\QueryException;

trait ClassifiesError
{
    private function isForeignKeyError(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'foreign key constraint') ||
            str_contains($e->getMessage(), 'FOREIGN KEY') ||
            $e->getCode() === '23000'; // SQLSTATE für Integrity Constraint Violation
    }

    private function isForeignKeyViolationError(QueryException $e): bool
    {
        // PostgreSQL: 23503, MySQL: 1452 (SQLSTATE 23000 with FK message)
        if ($e->getCode() === '23503') {
            return true;
        }

        if ($e->getCode() === '1452') {
            return true;
        }

        if (str_contains($e->getMessage(), 'violates foreign key constraint')) {
            return true;
        }

        return str_contains($e->getMessage(), 'Cannot add or update a child row') && str_contains($e->getMessage(), 'foreign key constraint');
    }

    private function isPermissionError(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'Access denied') ||
            str_contains($e->getMessage(), 'permission denied') ||
            str_contains($e->getMessage(), 'insufficient privileges') ||
            $e->getCode() === '42000'; // SQLSTATE für Syntax/Access Error
    }

    private function isTableNotFoundError(QueryException $e): bool
    {
        return str_contains($e->getMessage(), "doesn't exist") ||
            str_contains($e->getMessage(), 'does not exist') ||
            $e->getCode() === '42S02'; // SQLSTATE für Table Not Found
    }

    private function isTemporaryError(QueryException $e): bool
    {
        // Netzwerk-Timeouts, Deadlocks, etc.
        return str_contains($e->getMessage(), 'timeout') ||
            str_contains($e->getMessage(), 'deadlock') ||
            str_contains($e->getMessage(), 'connection lost') ||
            str_contains($e->getMessage(), 'server has gone away');
    }

    private function isUniqueConstraintError(QueryException $e): bool
    {
        // PostgreSQL: 23505, MySQL: 1062 (SQLSTATE 23000)
        if ($e->getCode() === '23505') {
            return true;
        }

        if ($e->getCode() === '1062') {
            return true;
        }

        if (str_contains($e->getMessage(), 'Duplicate entry')) {
            return true;
        }

        if (str_contains($e->getMessage(), 'duplicate key value violates unique constraint')) {
            return true;
        }

        return str_contains($e->getMessage(), 'UNIQUE constraint failed');
    }
}
