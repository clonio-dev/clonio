<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use App\Models\CloningRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * @property-read string $tableName
 * @property-read Model $run
 */
trait LogsProcessSteps
{
    /**
     * Track the last logged percentage per table to enable throttling.
     *
     * @var array<string, int>
     */
    private array $lastLoggedPercent = [];

    /**
     * Track the last logged time per table to enable time-based throttling.
     *
     * @var array<string, float>
     */
    private array $lastLoggedTime = [];

    /**
     * Minimum percentage change required before logging progress again.
     * This prevents excessive database writes during large transfers.
     */
    private int $progressLogThresholdPercent = 5;

    /**
     * Minimum seconds between progress log writes to the database.
     */
    private int $progressLogThresholdSeconds = 10;

    private function logInfo(string $event, string $message): void
    {
        $this->log('info', $event, $message);
    }

    private function logWarning(string $event, string $message): void
    {
        $this->log('warning', $event, $message);
    }

    private function logError(string $event, string $message): void
    {
        $this->log('error', $event, $message);
    }

    private function logSuccess(string $event, string $message, array $data = []): void
    {
        $this->log('success', $event, $message, $data);
    }

    private function logDebug(string $event, string $message): void
    {
        $this->log('debug', $event, $message);
    }

    /**
     * Log progress with throttling to prevent excessive database writes.
     *
     * Progress is only written to the database when:
     * - It's the first progress log for a table
     * - At least $progressLogThresholdSeconds have elapsed since last write
     * - The transfer is complete (100%)
     *
     * All progress is still logged to Laravel's log file for debugging.
     */
    private function logProgress(string $event, string $message, array $data = []): void
    {
        $tableName = property_exists($this, 'tableName') ? $this->tableName : '';
        $currentPercent = $data['percent'] ?? 0;

        // Check if we should write to database (throttled)
        if (! $this->shouldLogProgressToDatabase($tableName, $currentPercent)) {
            return;
        }

        // Update last logged percentage and time
        $this->lastLoggedPercent[$tableName] = $currentPercent;
        $this->lastLoggedTime[$tableName] = microtime(true);

        // Write to database
        if (isset($this->run) && $this->run instanceof CloningRun) {
            $this->run->log($event, [
                ...$data,
                'table' => $tableName,
                'message' => $message,
                'batch_id' => $this->batch()?->id,
            ], 'info');
        }
    }

    /**
     * Determine if progress should be logged to the database.
     *
     * Returns true for:
     * - First progress log for a table
     * - At least $progressLogThresholdSeconds have elapsed since last write
     * - Completion (100%)
     */
    private function shouldLogProgressToDatabase(string $tableName, int $currentPercent): bool
    {
        // Always log completion
        if ($currentPercent >= 100) {
            return true;
        }

        // First log for this table
        if (! isset($this->lastLoggedTime[$tableName])) {
            return true;
        }

        $elapsedSeconds = microtime(true) - $this->lastLoggedTime[$tableName];

        // Log if enough time has elapsed since last write
        return $elapsedSeconds >= $this->progressLogThresholdSeconds;
    }

    private function log(string $level, string $event, string $message, array $data = []): void
    {
        $tableName = property_exists($this, 'tableName') ? $this->tableName : '';

        if (isset($this->run) && $this->run instanceof CloningRun) {
            $this->run->log($event, [
                ...$data,
                'table' => $tableName,
                'message' => $message,
                'batch_id' => $this->batch()?->id,
            ], $level);

            return;
        }

        if ($level === 'success') {
            $level = 'info';
        }
        Log::{$level}("[Table: {$tableName}] [{$event}] {$message}");
    }

    private function logErrorMessage(string $message, Collection $connectionMap): void
    {
        $message = strtr($message, $connectionMap->all());

        if (isset($this->run) && $this->run instanceof CloningRun) {
            $this->run->update(['error_message' => $message]);
        }
    }
}
