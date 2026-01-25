<?php

declare(strict_types=1);

use App\Data\ConnectionDriverData;
use App\Data\MariaDBDriverData;

it('implements ConnectionDriverData interface', function (): void {
    $driver = new MariaDBDriverData(
        database: 'test_db',
        host: 'localhost',
        username: 'root',
        password: 'secret',
    );

    expect($driver)->toBeInstanceOf(ConnectionDriverData::class);
});

it('can be instantiated with required parameters', function (): void {
    $driver = new MariaDBDriverData(
        database: 'mydb',
        host: '127.0.0.1',
        username: 'user',
        password: 'pass',
    );

    expect($driver->database)->toBe('mydb')
        ->and($driver->host)->toBe('127.0.0.1')
        ->and($driver->username)->toBe('user')
        ->and($driver->password)->toBe('pass')
        ->and($driver->port)->toBe(3306);
});

it('has correct default values', function (): void {
    $driver = new MariaDBDriverData(
        database: 'mydb',
        host: 'localhost',
        username: 'root',
        password: '',
    );

    expect($driver->port)->toBe(3306)
        ->and($driver->engine)->toBeNull()
        ->and($driver->prefix)->toBe('')
        ->and($driver->prefixIndexes)->toBeTrue()
        ->and($driver->strict)->toBeTrue()
        ->and($driver->charset)->toBe('utf8mb4')
        ->and($driver->collation)->toBe('utf8mb4_unicode_ci')
        ->and($driver->socket)->toBe('')
        ->and($driver->ssl)->toBeNull()
        ->and($driver->url)->toBeNull();
});

it('converts to array with mariadb driver', function (): void {
    $driver = new MariaDBDriverData(
        database: 'testdb',
        host: 'localhost',
        username: 'root',
        password: 'password',
    );

    $array = $driver->toArray();

    expect($array['driver'])->toBe('mariadb');
});

it('array includes all expected keys', function (): void {
    $driver = new MariaDBDriverData(
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
        'unix_socket',
        'charset',
        'collation',
        'prefix',
        'prefix_indexes',
        'strict',
        'engine',
        'options',
    ]);
});
