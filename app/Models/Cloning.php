<?php

declare(strict_types=1);

namespace App\Models;

use Exception;
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
 * @property string $title
 * @property int $source_connection_id
 * @property int $target_connection_id
 * @property array<string, mixed>|null $anonymization_config
 * @property string|null $schedule
 * @property bool $is_scheduled
 * @property bool $is_paused
 * @property int $consecutive_failures
 * @property Carbon|null $next_run_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read DatabaseConnection $sourceConnection
 * @property-read DatabaseConnection $targetConnection
 * @property-read Collection<int, CloningRun> $runs
 * @property-read CloningRun|null $latestRun
 *
 * @mixin Model
 */
class Cloning extends Model
{
    /** @use HasFactory<\Database\Factories\CloningFactory> */
    use HasFactory;

    protected $table = 'clonings';

    protected $fillable = [
        'user_id',
        'title',
        'source_connection_id',
        'target_connection_id',
        'anonymization_config',
        'schedule',
        'is_scheduled',
        'is_paused',
        'consecutive_failures',
        'next_run_at',
    ];

    /**
     * Calculate the next run time from a cron expression.
     */
    public static function calculateNextRunAt(?string $cronExpression): ?Carbon
    {
        if (in_array($cronExpression, [null, '', '0'], true)) {
            return null;
        }

        try {
            $cron = new \Cron\CronExpression($cronExpression);

            return \Illuminate\Support\Facades\Date::instance($cron->getNextRunDate());
        } catch (Exception) {
            return null;
        }
    }

    public function casts(): array
    {
        return [
            'anonymization_config' => 'array',
            'is_scheduled' => 'boolean',
            'is_paused' => 'boolean',
            'consecutive_failures' => 'integer',
            'next_run_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, Cloning>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<DatabaseConnection, Cloning>
     */
    public function sourceConnection(): BelongsTo
    {
        return $this->belongsTo(DatabaseConnection::class, 'source_connection_id');
    }

    /**
     * @return BelongsTo<DatabaseConnection, Cloning>
     */
    public function targetConnection(): BelongsTo
    {
        return $this->belongsTo(DatabaseConnection::class, 'target_connection_id');
    }

    /**
     * @return HasMany<CloningRun, Cloning>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(CloningRun::class, 'cloning_id');
    }

    /**
     * Get the latest run for this cloning.
     */
    public function latestRun(): ?CloningRun
    {
        return $this->runs()->latest('id')->first();
    }

    /**
     * Check if this cloning can run (is scheduled and not paused).
     */
    public function canRun(): bool
    {
        return $this->is_scheduled && ! $this->is_paused;
    }

    /**
     * Pause this cloning.
     */
    public function pause(): void
    {
        $this->update(['is_paused' => true]);
    }

    /**
     * Resume this cloning and reset failure counter.
     */
    public function resume(): void
    {
        $this->update([
            'is_paused' => false,
            'consecutive_failures' => 0,
        ]);
    }

    /**
     * Record a failure and auto-pause if threshold reached.
     *
     * @return bool True if the cloning was auto-paused
     */
    public function recordFailure(): bool
    {
        $newCount = $this->consecutive_failures + 1;
        $shouldPause = $newCount >= 3;

        $this->update([
            'consecutive_failures' => $newCount,
            'is_paused' => $shouldPause,
        ]);

        return $shouldPause;
    }

    /**
     * Record a successful run and reset failure counter.
     */
    public function recordSuccess(): void
    {
        if ($this->consecutive_failures > 0) {
            $this->update(['consecutive_failures' => 0]);
        }
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
    protected function scheduled(Builder $query): Builder
    {
        return $query->where('is_scheduled', true);
    }

    #[Scope]
    protected function notPaused(Builder $query): Builder
    {
        return $query->where('is_paused', false);
    }
}
