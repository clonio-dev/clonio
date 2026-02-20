<?php

declare(strict_types=1);

use App\Services\TableInformationRetrievalService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

it('returns record count for a table', function (): void {
    $db = tempnam(sys_get_temp_dir(), 'test_');
    @unlink($db);
    $db .= '.sqlite';
    touch($db);

    config(['database.connections.test_db' => [
        'driver' => 'sqlite',
        'database' => $db,
    ]]);
    DB::purge('test_db');

    DB::connection('test_db')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
    });

    DB::connection('test_db')->table('users')->insert(['name' => 'John']);
    DB::connection('test_db')->table('users')->insert(['name' => 'Jane']);
    DB::connection('test_db')->table('users')->insert(['name' => 'Bob']);

    $service = new TableInformationRetrievalService(
        DB::connection('test_db'),
        'users'
    );

    expect($service->recordCount())->toBe(3);

    @unlink($db);
});

it('returns query builder for a table', function (): void {
    $db = tempnam(sys_get_temp_dir(), 'test_');
    @unlink($db);
    $db .= '.sqlite';
    touch($db);

    config(['database.connections.test_db' => [
        'driver' => 'sqlite',
        'database' => $db,
    ]]);
    DB::purge('test_db');

    DB::connection('test_db')->getSchemaBuilder()->create('posts', function ($table): void {
        $table->id();
        $table->string('title');
    });

    $service = new TableInformationRetrievalService(
        DB::connection('test_db'),
        'posts'
    );

    expect($service->query())->toBeInstanceOf(Builder::class);
    expect($service->query()->count())->toBe(0);

    @unlink($db);
});

it('returns primary key columns as order columns when primary key exists', function (): void {
    $db = tempnam(sys_get_temp_dir(), 'test_');
    @unlink($db);
    $db .= '.sqlite';
    touch($db);

    config(['database.connections.test_db' => [
        'driver' => 'sqlite',
        'database' => $db,
    ]]);
    DB::purge('test_db');

    // Create table with primary key
    DB::connection('test_db')->getSchemaBuilder()->create('users', function ($table): void {
        $table->id();
        $table->string('name');
    });

    $service = new TableInformationRetrievalService(
        DB::connection('test_db'),
        'users'
    );

    $orderColumns = $service->orderColumns();

    expect($orderColumns)->toBeArray()
        ->and($orderColumns)->toContain('id');

    @unlink($db);
});

it('returns first column as order column when table has no primary key', function (): void {
    $db = tempnam(sys_get_temp_dir(), 'test_');
    @unlink($db);
    $db .= '.sqlite';
    touch($db);

    config(['database.connections.test_db' => [
        'driver' => 'sqlite',
        'database' => $db,
    ]]);
    DB::purge('test_db');

    // Create table WITHOUT primary key
    DB::connection('test_db')->statement('
        CREATE TABLE logs (
            message TEXT NOT NULL,
            created_at DATETIME,
            level VARCHAR(255)
        )
    ');

    $service = new TableInformationRetrievalService(
        DB::connection('test_db'),
        'logs'
    );

    $orderColumns = $service->orderColumns();

    expect($orderColumns)->toBeArray()
        ->and($orderColumns)->toHaveCount(1)
        ->and($orderColumns[0])->toBe('message'); // First column

    @unlink($db);
});

it('returns composite primary key columns in order', function (): void {
    $db = tempnam(sys_get_temp_dir(), 'test_');
    @unlink($db);
    $db .= '.sqlite';
    touch($db);

    config(['database.connections.test_db' => [
        'driver' => 'sqlite',
        'database' => $db,
    ]]);
    DB::purge('test_db');

    // Create table with composite primary key
    DB::connection('test_db')->statement('
        CREATE TABLE user_roles (
            user_id INTEGER NOT NULL,
            role_id INTEGER NOT NULL,
            assigned_at DATETIME,
            PRIMARY KEY (user_id, role_id)
        )
    ');

    $service = new TableInformationRetrievalService(
        DB::connection('test_db'),
        'user_roles'
    );

    $orderColumns = $service->orderColumns();

    expect($orderColumns)->toBeArray()
        ->and($orderColumns)->toHaveCount(2)
        ->and($orderColumns)->toContain('user_id')
        ->and($orderColumns)->toContain('role_id');

    @unlink($db);
});
