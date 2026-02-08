<?php

declare(strict_types=1);

use App\Enums\DatabaseConnectionTypes;

it('has all expected cases', function (): void {
    expect(DatabaseConnectionTypes::cases())->toHaveCount(5);
});

it('has correct string values', function (): void {
    expect(DatabaseConnectionTypes::MYSQL->value)->toBe('mysql')
        ->and(DatabaseConnectionTypes::MARIADB->value)->toBe('mariadb')
        ->and(DatabaseConnectionTypes::POSTGRESQL->value)->toBe('pgsql')
        ->and(DatabaseConnectionTypes::MSSQLSERVER->value)->toBe('sqlserver')
        ->and(DatabaseConnectionTypes::SQLITE->value)->toBe('sqlite');
});

it('returns correct labels', function (): void {
    expect(DatabaseConnectionTypes::MYSQL->getLabel())->toBe('MySQL')
        ->and(DatabaseConnectionTypes::MARIADB->getLabel())->toBe('MariaDB')
        ->and(DatabaseConnectionTypes::POSTGRESQL->getLabel())->toBe('PostgreSQL')
        ->and(DatabaseConnectionTypes::MSSQLSERVER->getLabel())->toBe('SQL Server')
        ->and(DatabaseConnectionTypes::SQLITE->getLabel())->toBe('SQLite');
});

it('can be created from string value', function (): void {
    expect(DatabaseConnectionTypes::from('mysql'))->toBe(DatabaseConnectionTypes::MYSQL)
        ->and(DatabaseConnectionTypes::from('mariadb'))->toBe(DatabaseConnectionTypes::MARIADB)
        ->and(DatabaseConnectionTypes::from('pgsql'))->toBe(DatabaseConnectionTypes::POSTGRESQL)
        ->and(DatabaseConnectionTypes::from('sqlserver'))->toBe(DatabaseConnectionTypes::MSSQLSERVER)
        ->and(DatabaseConnectionTypes::from('sqlite'))->toBe(DatabaseConnectionTypes::SQLITE);
});

it('throws exception for invalid value', function (): void {
    DatabaseConnectionTypes::from('invalid');
})->throws(ValueError::class);
