<?php

namespace App\Models;

use App\Enums\TransferRunLogLevel;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $run_id
 * @property TransferRunLogLevel $level
 * @property string $event_type
 * @property string $message
 * @property array $data
 * @property Carbon $created_at
 * @property-read TransferRun $run
 */
class TransferRunLog extends Model
{
    /** @use HasFactory<\Database\Factories\TransferRunLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'run_id',
        'level',
        'event_type',
        'message',
        'data',
        'created_at',
    ];

    public function casts()
    {
        return [
            'level' => TransferRunLogLevel::class,
            'data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TransferRun, TransferRunLog>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(TransferRun::class, 'run_id');
    }

    /**
     * Scopes
     */
    #[Scope]
    protected function errors(Builder $query): Builder
    {
        return $query->where('level', TransferRunLogLevel::ERROR->value);
    }

    #[Scope]
    protected function warnings(Builder $query): Builder
    {
        return $query->where('level', TransferRunLogLevel::WARNING->value);
    }

    #[Scope]
    protected function info(Builder $query): Builder
    {
        return $query->where('level', TransferRunLogLevel::INFO->value);
    }

    #[Scope]
    protected function byEvent(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }
}
