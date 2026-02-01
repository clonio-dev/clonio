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
        }

        if ($level === 'success') {
            $level = 'info';
        }
        Log::{$level}("[Table: {$tableName}] [{$event}] {$message}");
    }

    private function logErrorMessage(string $message, Collection $connectionMap): void
    {
        Log::debug(__METHOD__ . '::before: ' . $message);

        $message = strtr($message, $connectionMap->all());

        Log::debug(__METHOD__ . '::after: ' . $message, ['conecctions' => $connectionMap->all()]);

        if (isset($this->run) && $this->run instanceof CloningRun) {
            $this->run->update(['error_message' => $message]);
        }
    }
}
