<?php

namespace App\Models;

use App\Enums\TransferRunLogLevel;
use App\Enums\TransferRunStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $source_connection_id
 * @property int $target_connection_id
 * @property string $script
 * @property string $batch_id
 * @property TransferRunStatus $status
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property int $current_step
 * @property int $total_steps
 * @property int $progress_percent
 * @property string|null $error_message
 * @property-read User $user
 * @property-read DatabaseConnection $sourceConnection
 * @property-read DatabaseConnection $targetConnection
 * @property-read Collection<int, TransferRunLog> $logs
 * @property-read int|null $duration
 *
 * @mixin Model
 */
class TransferRun extends Model
{
    /** @use HasFactory<\Database\Factories\TransferRunFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'source_connection_id',
        'target_connection_id',
        'script',
        'batch_id',
        'status',
        'started_at',
        'finished_at',
        'current_step',
        'total_steps',
        'progress_percent',
        'error_message',
    ];

    public function casts()
    {
        return [
            'status' => TransferRunStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'current_step' => 'integer',
            'total_steps' => 'integer',
            'progress_percent' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, TransferRun>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<DatabaseConnection, TransferRun>
     */
    public function sourceConnection(): BelongsTo
    {
        return $this->belongsTo(DatabaseConnection::class, 'source_connection_id');
    }

    /**
     * @return BelongsTo<DatabaseConnection, TransferRun>
     */
    public function targetConnection(): BelongsTo
    {
        return $this->belongsTo(DatabaseConnection::class, 'target_connection_id');
    }

    /**
     * @return HasMany<TransferRunLog, TransferRun>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(TransferRunLog::class, 'run_id');
    }

    public function log(string $eventType, array $data = [], string|TransferRunLogLevel $level = TransferRunLogLevel::INFO, ?string $message = null): TransferRunLog
    {
        return $this->logs()->create([
            'level' => $level instanceof TransferRunLogLevel ? $level->value : $level,
            'event_type' => $eventType,
            'message' => $message ?? $this->generateMessage($eventType, $data),
            'data' => $data,
            'created_at' => now(),
        ]);
    }

    /**
     * Accessors
     */
    protected function getDurationAttribute(): ?int
    {
        if (! $this->finished_at || ! $this->started_at) {
            return null;
        }

        return ceil($this->started_at->diffInSeconds($this->finished_at));
    }

    /**
     * Scopes
     */
    #[Scope]
    protected function forUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    #[Scope]
    protected function forBatch(Builder $query, string $batchId): Builder
    {
        return $query->where('batch_id', $batchId);
    }

    #[Scope]
    protected function running(Builder $query): Builder
    {
        return $query->whereIn('status', [TransferRunStatus::QUEUED->value, TransferRunStatus::PROCESSING->value]);
    }

    #[Scope]
    protected function completed(Builder $query): Builder
    {
        return $query->where('status', TransferRunStatus::COMPLETED->value);
    }

    #[Scope]
    protected function failed(Builder $query): Builder
    {
        return $query->where('status', TransferRunStatus::FAILED->value);
    }

    /**
     * Generate human-readable message from event type
     *
     * @param  array{total_jobs: int, table: string, rows_processed: int, error: string, duration: int, processed_before_cancel: int}  $data
     */
    private function generateMessage(string $eventType, array $data): string
    {
        $data = array_merge([
            'total_jobs' => 0,
            'table' => 'unknown',
            'rows_processed' => 0,
            'duration' => 0,
            'error' => 'unknown error',
            'processed_before_cancel' => 0,
        ], $data);

        return match ($eventType) {
            'batch_started' => "Batch started with {$data['total_jobs']} jobs",
            'table_started' => "Processing table: {$data['table']}",
            'table_completed' => "Table {$data['table']} completed: {$data['rows_processed']} rows",
            'table_failed' => "Table {$data['table']} failed: {$data['error']}",
            'batch_completed' => "Batch completed in {$data['duration']}s",
            'batch_failed' => "Batch failed: {$data['error']}",
            'batch_cancelled' => "Batch cancelled after {$data['processed_before_cancel']} jobs",
            default => $eventType,
        };
    }
}
