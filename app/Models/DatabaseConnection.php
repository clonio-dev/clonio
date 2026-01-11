<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\ConnectionData;
use App\Data\MariaDBDriverData;
use App\Data\MysqlDriverData;
use App\Data\PostgresSqlDriverData;
use App\Data\SqliteDriverData;
use App\Data\SqlServerDriverData;
use App\Enums\DatabaseConnectionTypes;
use Exception;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property DatabaseConnectionTypes $type
 * @property string $host
 * @property int $port
 * @property string $database
 * @property string $username
 * @property string $password
 * @property bool $is_production_stage
 * @property null|Carbon $last_tested_at
 * @property-read User $user
 *
 * @mixin Model
 */
class DatabaseConnection extends Model
{
    /** @use HasFactory<\Database\Factories\DatabaseConnectionFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'host',
        'port',
        'database',
        'username',
        'password',
        'is_production_stage',
        'last_tested_at',
    ];

    protected $hidden = ['password'];

    public function casts()
    {
        return [
            'type' => DatabaseConnectionTypes::class,
            'port' => 'integer',
            'is_production_stage' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, DatabaseConnection>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toConnectionDataDto(): ConnectionData
    {
        $driverData = match ($this->type) {
            DatabaseConnectionTypes::MYSQL => new MysqlDriverData(
                database: $this->database,
                host: $this->host,
                username: $this->username,
                password: $this->password,
                port: $this->port,
            ),
            DatabaseConnectionTypes::MARIADB => new MariaDBDriverData(
                database: $this->database,
                host: $this->host,
                username: $this->username,
                password: $this->password,
                port: $this->port,
            ),
            DatabaseConnectionTypes::POSTGRESQL => new PostgresSqlDriverData(
                database: $this->database,
                host: $this->host,
                username: $this->username,
                password: $this->password,
                port: $this->port,
            ),
            DatabaseConnectionTypes::MSSQLSERVER => new SqlServerDriverData(
                database: $this->database,
                host: $this->host,
                username: $this->username,
                password: $this->password,
                port: $this->port,
            ),
            DatabaseConnectionTypes::SQLITE => new SqliteDriverData($this->database),
            default => throw new Exception("Unsupported connection type: {$this->type}")
        };

        return new ConnectionData(
            $this->name,
            $driverData,
        );
    }

    /**
     * Encrypt password when setting
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString((string) $value) : '',
            set: fn ($value) => $value ? Crypt::encryptString((string) $value) : '',
        );
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
    protected function ofType(Builder $query, string|DatabaseConnectionTypes $type): Builder
    {
        if ($type instanceof DatabaseConnectionTypes) {
            $type = $type->value;
        }

        return $query->where('type', $type);
    }

    #[Scope]
    protected function tested(Builder $query): Builder
    {
        return $query->whereNotNull('last_tested_at');
    }

    #[Scope]
    protected function testDatabases(Builder $query): Builder
    {
        return $query->where('is_production_stage', false);
    }

    #[Scope]
    protected function prodDatabases(Builder $query): Builder
    {
        return $query->where('is_production_stage', true);
    }
}
