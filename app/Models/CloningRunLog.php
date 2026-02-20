<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CloningRunLogLevel;
use Database\Factories\CloningRunLogFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $run_id
 * @property CloningRunLogLevel $level
 * @property string $event_type
 * @property string $message
 * @property array<string, mixed> $data
 * @property Carbon $created_at
 * @property-read CloningRun $run
 */
class CloningRunLog extends Model
{
    /** @use HasFactory<CloningRunLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'cloning_run_logs';

    protected $fillable = [
        'run_id',
        'level',
        'event_type',
        'message',
        'data',
        'created_at',
    ];

    public function casts(): array
    {
        return [
            'level' => CloningRunLogLevel::class,
            'data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<CloningRun, CloningRunLog>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(CloningRun::class, 'run_id');
    }

    /**
     * Scopes
     */
    #[Scope]
    protected function errors(Builder $query): Builder
    {
        return $query->where('level', CloningRunLogLevel::ERROR->value);
    }

    #[Scope]
    protected function warnings(Builder $query): Builder
    {
        return $query->where('level', CloningRunLogLevel::WARNING->value);
    }

    #[Scope]
    protected function info(Builder $query): Builder
    {
        return $query->where('level', CloningRunLogLevel::INFO->value);
    }

    #[Scope]
    protected function byEvent(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }
}
