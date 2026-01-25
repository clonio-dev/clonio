<?php

declare(strict_types=1);

use App\Data\ConnectionDriverData;
use App\Data\MysqlDriverData;

it('implements ConnectionDriverData interface', function (): void {
    $driver = new MysqlDriverData(
        database: 'test_db',
        host: 'localhost',
        username: 'root',
        password: 'secret',
    );

    expect($driver)->toBeInstanceOf(ConnectionDriverData::class);
});

it('can be instantiated with required parameters', function (): void {
    $driver = new MysqlDriverData(
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
    $driver = new MysqlDriverData(
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

it('can be instantiated with all parameters', function (): void {
    $driver = new MysqlDriverData(
        database: 'production',
        host: 'db.example.com',
        username: 'admin',
        password: 'secure123',
        port: 3307,
        engine: 'InnoDB',
        prefix: 'app_',
        prefixIndexes: false,
        strict: false,
        charset: 'utf8',
        collation: 'utf8_general_ci',
        socket: '/var/run/mysqld/mysqld.sock',
        ssl: '/path/to/ca.pem',
        url: 'mysql://admin:pass@host/db',
    );

    expect($driver->port)->toBe(3307)
        ->and($driver->engine)->toBe('InnoDB')
        ->and($driver->prefix)->toBe('app_')
        ->and($driver->prefixIndexes)->toBeFalse()
        ->and($driver->strict)->toBeFalse()
        ->and($driver->charset)->toBe('utf8')
        ->and($driver->collation)->toBe('utf8_general_ci')
        ->and($driver->socket)->toBe('/var/run/mysqld/mysqld.sock')
        ->and($driver->ssl)->toBe('/path/to/ca.pem')
        ->and($driver->url)->toBe('mysql://admin:pass@host/db');
});

it('converts to array correctly', function (): void {
    $driver = new MysqlDriverData(
        database: 'testdb',
        host: 'localhost',
        username: 'root',
        password: 'password',
        port: 3306,
        charset: 'utf8mb4',
    );

    $array = $driver->toArray();

    expect($array)->toBeArray()
        ->and($array['driver'])->toBe('mysql')
        ->and($array['host'])->toBe('localhost')
        ->and($array['port'])->toBe(3306)
        ->and($array['database'])->toBe('testdb')
        ->and($array['username'])->toBe('root')
        ->and($array['password'])->toBe('password')
        ->and($array['charset'])->toBe('utf8mb4');
});

it('array includes all expected keys', function (): void {
    $driver = new MysqlDriverData(
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
