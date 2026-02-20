<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

test('logs SQL queries when enabled', function (): void {
    config(['logging.log_sql_queries' => true]);

    // Re-boot the provider so it registers the DB listener with updated config
    app()->make(AppServiceProvider::class, ['app' => app()])->boot();

    Log::shouldReceive('debug')
        ->atLeast()->once()
        ->withArgs(fn (string $message): bool => str_starts_with($message, '[SQL] '));

    DB::select('SELECT 1');
});

test('does not log SQL queries when disabled', function (): void {
    config(['logging.log_sql_queries' => false]);

    Log::shouldReceive('debug')
        ->withArgs(fn (string $message): bool => str_starts_with($message, '[SQL] '))
        ->never();

    DB::select('SELECT 1');
});
