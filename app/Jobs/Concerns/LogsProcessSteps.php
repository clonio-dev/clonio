<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use App\Models\TransferRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * @property-read string $tableName
 * @property-read Model $run
 */
trait LogsProcessSteps
{
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

    private function logSuccess(string $event, string $message): void
    {
        $this->log('info', $event, $message);
    }

    private function logDebug(string $event, string $message): void
    {
        // Nur in Development-Umgebung
        if (config('app.debug')) {
            $this->log('info', $event, $message);
        }
    }

    private function log(string $level, string $event, string $message): void
    {
        $tableName = property_exists($this, 'tableName') ? $this->tableName : '';

        if (isset($this->run) && $this->run instanceof TransferRun) {
            $this->run->log($event, [
                'table' => $tableName,
                'message' => $message,
                'batch_id' => $this->batch()?->id,
            ], $level);
        }

        Log::{$level}("[Table: {$tableName}] [{$event}] {$message}");
    }
}
