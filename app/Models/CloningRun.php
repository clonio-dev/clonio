<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CloningRunLogLevel;
use App\Enums\CloningRunStatus;
use Illuminate\Bus\Batch;
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
 * @property int|null $cloning_id
 * @property string $batch_id
 * @property CloningRunStatus $status
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property int $current_step
 * @property int $total_steps
 * @property int $progress_percent
 * @property string|null $error_message
 * @property-read User $user
 * @property-read Cloning|null $cloning
 * @property-read ?Batch $batch
 * @property-read Collection<int, CloningRunLog> $logs
 * @property-read int|null $duration
 *
 * @mixin Model
 */
class CloningRun extends Model
{
    /** @use HasFactory<\Database\Factories\CloningRunFactory> */
    use HasFactory;

    protected $table = 'cloning_runs';

    protected $fillable = [
        'user_id',
        'cloning_id',
        'batch_id',
        'status',
        'started_at',
        'finished_at',
        'current_step',
        'total_steps',
        'progress_percent',
        'error_message',
    ];

    public function casts(): array
    {
        return [
            'status' => CloningRunStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'current_step' => 'integer',
            'total_steps' => 'integer',
            'progress_percent' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, CloningRun>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Cloning, CloningRun>
     */
    public function cloning(): BelongsTo
    {
        return $this->belongsTo(Cloning::class);
    }

    /**
     * @return BelongsTo<Batch, CloningRun>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    /**
     * @return HasMany<CloningRunLog, CloningRun>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(CloningRunLog::class, 'run_id');
    }

    public function log(string $eventType, array $data = [], string|CloningRunLogLevel $level = CloningRunLogLevel::INFO, ?string $message = null): CloningRunLog
    {
        return $this->logs()->create([
            'level' => $level instanceof CloningRunLogLevel ? $level->value : $level,
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

        return (int) ceil($this->started_at->diffInSeconds($this->finished_at));
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
    protected function forCloning(Builder $query, int $cloningId): Builder
    {
        return $query->where('cloning_id', $cloningId);
    }

    #[Scope]
    protected function forBatch(Builder $query, string $batchId): Builder
    {
        return $query->where('batch_id', $batchId);
    }

    #[Scope]
    protected function running(Builder $query): Builder
    {
        return $query->whereIn('status', [CloningRunStatus::QUEUED->value, CloningRunStatus::PROCESSING->value]);
    }

    #[Scope]
    protected function completed(Builder $query): Builder
    {
        return $query->where('status', CloningRunStatus::COMPLETED->value);
    }

    #[Scope]
    protected function failed(Builder $query): Builder
    {
        return $query->where('status', CloningRunStatus::FAILED->value);
    }

    /**
     * Generate human-readable message from event type
     *
     * @param  array{total_jobs?: int, table?: string, rows_processed?: int, error?: string, duration?: int, processed_before_cancel?: int}  $data
     */
    private function generateMessage(string $eventType, array $data): string
    {
        if (array_key_exists('message', $data)) {
            return $data['message'];
        }

        $data = array_merge([
            'total_jobs' => 0,
            'table' => 'unknown',
            'rows_processed' => 0,
            'duration' => 0,
            'error' => 'unknown error',
            'processed_before_cancel' => 0,
        ], $data);

        return match ($eventType) {
            'cloning_run_created' => 'Cloning run created',
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
