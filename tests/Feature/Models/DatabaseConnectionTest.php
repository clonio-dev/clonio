<?php

declare(strict_types=1);

use App\Enums\DatabaseConnectionTypes;
use App\Models\DatabaseConnection;

it('stores dbms version when marking connection as connected', function (): void {
    $connection = DatabaseConnection::factory()->mysql()->create();

    $connection->markConnected('Healthy', '8.0.35');

    $connection->refresh();

    expect($connection->dbms_version)->toBe('8.0.35')
        ->and($connection->is_connectable)->toBeTrue()
        ->and($connection->last_test_result)->toBe('Healthy');
});

it('corrects mysql type to mariadb when version contains mariadb', function (): void {
    $connection = DatabaseConnection::factory()->mysql()->create();

    expect($connection->type)->toBe(DatabaseConnectionTypes::MYSQL);

    $connection->markConnected('Healthy', '10.11.6-MariaDB');

    $connection->refresh();

    expect($connection->type)->toBe(DatabaseConnectionTypes::MARIADB)
        ->and($connection->dbms_version)->toBe('10.11.6-MariaDB');
});

it('corrects mariadb type to mysql when version does not contain mariadb', function (): void {
    $connection = DatabaseConnection::factory()->mariadb()->create();

    expect($connection->type)->toBe(DatabaseConnectionTypes::MARIADB);

    $connection->markConnected('Healthy', '8.0.35-0ubuntu0.22.04.1');

    $connection->refresh();

    expect($connection->type)->toBe(DatabaseConnectionTypes::MYSQL)
        ->and($connection->dbms_version)->toBe('8.0.35-0ubuntu0.22.04.1');
});

it('does not change type when mysql version matches mysql type', function (): void {
    $connection = DatabaseConnection::factory()->mysql()->create();

    $connection->markConnected('Healthy', '8.0.35');

    $connection->refresh();

    expect($connection->type)->toBe(DatabaseConnectionTypes::MYSQL);
});

it('does not change type when mariadb version matches mariadb type', function (): void {
    $connection = DatabaseConnection::factory()->mariadb()->create();

    $connection->markConnected('Healthy', '10.11.6-MariaDB');

    $connection->refresh();

    expect($connection->type)->toBe(DatabaseConnectionTypes::MARIADB);
});

it('does not change type for postgresql connections', function (): void {
    $connection = DatabaseConnection::factory()->pgsql()->create();

    $connection->markConnected('Healthy', 'PostgreSQL 15.4');

    $connection->refresh();

    expect($connection->type)->toBe(DatabaseConnectionTypes::POSTGRESQL);
});

it('handles null dbms version gracefully', function (): void {
    $connection = DatabaseConnection::factory()->mysql()->create();

    $connection->markConnected('Healthy');

    $connection->refresh();

    expect($connection->dbms_version)->toBeNull()
        ->and($connection->is_connectable)->toBeTrue();
});

it('handles case insensitive mariadb detection', function (): void {
    $connection = DatabaseConnection::factory()->mysql()->create();

    $connection->markConnected('Healthy', '10.11.6-MARIADB-log');

    $connection->refresh();

    expect($connection->type)->toBe(DatabaseConnectionTypes::MARIADB);
});
