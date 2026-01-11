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
}
