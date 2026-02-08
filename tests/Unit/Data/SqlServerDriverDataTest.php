<?php

declare(strict_types=1);

use App\Data\ConnectionDriverData;
use App\Data\SqlServerDriverData;

it('implements ConnectionDriverData interface', function (): void {
    $driver = new SqlServerDriverData(
        database: 'test_db',
        host: 'localhost',
        username: 'sa',
        password: 'secret',
    );

    expect($driver)->toBeInstanceOf(ConnectionDriverData::class);
});

it('can be instantiated with required parameters', function (): void {
    $driver = new SqlServerDriverData(
        database: 'mydb',
        host: '127.0.0.1',
        username: 'user',
        password: 'pass',
    );

    expect($driver->database)->toBe('mydb')
        ->and($driver->host)->toBe('127.0.0.1')
        ->and($driver->username)->toBe('user')
        ->and($driver->password)->toBe('pass')
        ->and($driver->port)->toBe(1433);
});

it('has correct default values', function (): void {
    $driver = new SqlServerDriverData(
        database: 'mydb',
        host: 'localhost',
        username: 'sa',
        password: '',
    );

    expect($driver->port)->toBe(1433)
        ->and($driver->prefix)->toBe('')
        ->and($driver->prefixIndexes)->toBeTrue()
        ->and($driver->charset)->toBe('utf8')
        ->and($driver->url)->toBeNull();
});

it('can be instantiated with all parameters', function (): void {
    $driver = new SqlServerDriverData(
        database: 'production',
        host: 'sqlserver.example.com',
        username: 'admin',
        password: 'secure123',
        port: 1434,
        prefix: 'app_',
        prefixIndexes: false,
        charset: 'latin1',
        url: 'sqlsrv://admin:pass@host/db',
    );

    expect($driver->port)->toBe(1434)
        ->and($driver->prefix)->toBe('app_')
        ->and($driver->prefixIndexes)->toBeFalse()
        ->and($driver->charset)->toBe('latin1')
        ->and($driver->url)->toBe('sqlsrv://admin:pass@host/db');
});

it('converts to array correctly', function (): void {
    $driver = new SqlServerDriverData(
        database: 'testdb',
        host: 'localhost',
        username: 'sa',
        password: 'password',
    );

    $array = $driver->toArray();

    expect($array)->toBeArray()
        ->and($array['driver'])->toBe('sqlsrv')
        ->and($array['host'])->toBe('localhost')
        ->and($array['port'])->toBe(1433)
        ->and($array['database'])->toBe('testdb')
        ->and($array['username'])->toBe('sa')
        ->and($array['password'])->toBe('password');
});

it('array includes all expected keys', function (): void {
    $driver = new SqlServerDriverData(
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
    ]);
});
