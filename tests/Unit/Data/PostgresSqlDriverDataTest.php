<?php

declare(strict_types=1);

use App\Data\ConnectionDriverData;
use App\Data\PostgresSqlDriverData;

it('implements ConnectionDriverData interface', function (): void {
    $driver = new PostgresSqlDriverData(
        database: 'test_db',
        host: 'localhost',
        username: 'postgres',
        password: 'secret',
    );

    expect($driver)->toBeInstanceOf(ConnectionDriverData::class);
});

it('can be instantiated with required parameters', function (): void {
    $driver = new PostgresSqlDriverData(
        database: 'mydb',
        host: '127.0.0.1',
        username: 'user',
        password: 'pass',
    );

    expect($driver->database)->toBe('mydb')
        ->and($driver->host)->toBe('127.0.0.1')
        ->and($driver->username)->toBe('user')
        ->and($driver->password)->toBe('pass')
        ->and($driver->port)->toBe(5432);
});

it('has correct default values', function (): void {
    $driver = new PostgresSqlDriverData(
        database: 'mydb',
        host: 'localhost',
        username: 'postgres',
        password: '',
    );

    expect($driver->port)->toBe(5432)
        ->and($driver->prefix)->toBe('')
        ->and($driver->prefixIndexes)->toBeTrue()
        ->and($driver->charset)->toBe('utf8')
        ->and($driver->schema)->toBe('public')
        ->and($driver->ssl)->toBe('prefer')
        ->and($driver->url)->toBeNull();
});

it('can be instantiated with all parameters', function (): void {
    $driver = new PostgresSqlDriverData(
        database: 'production',
        host: 'db.example.com',
        username: 'admin',
        password: 'secure123',
        port: 5433,
        prefix: 'app_',
        prefixIndexes: false,
        charset: 'latin1',
        schema: 'myschema',
        ssl: 'require',
        url: 'postgresql://admin:pass@host/db',
    );

    expect($driver->port)->toBe(5433)
        ->and($driver->prefix)->toBe('app_')
        ->and($driver->prefixIndexes)->toBeFalse()
        ->and($driver->charset)->toBe('latin1')
        ->and($driver->schema)->toBe('myschema')
        ->and($driver->ssl)->toBe('require')
        ->and($driver->url)->toBe('postgresql://admin:pass@host/db');
});

it('converts to array correctly', function (): void {
    $driver = new PostgresSqlDriverData(
        database: 'testdb',
        host: 'localhost',
        username: 'postgres',
        password: 'password',
        schema: 'public',
    );

    $array = $driver->toArray();

    expect($array)->toBeArray()
        ->and($array['driver'])->toBe('pgsql')
        ->and($array['host'])->toBe('localhost')
        ->and($array['port'])->toBe(5432)
        ->and($array['database'])->toBe('testdb')
        ->and($array['username'])->toBe('postgres')
        ->and($array['password'])->toBe('password')
        ->and($array['search_path'])->toBe('public')
        ->and($array['sslmode'])->toBe('prefer');
});

it('array includes all expected keys', function (): void {
    $driver = new PostgresSqlDriverData(
        database: 'db',
        host: 'host',
        username: 'user',
        password: 'pass',
    );

    $array = $driver->toArray();

    expect($array)->toHaveKeys([
        'driver',
        'url',
        'host',
        'port',
        'database',
        'username',
        'password',
        'charset',
        'prefix',
        'prefix_indexes',
        'search_path',
        'sslmode',
    ]);
});
