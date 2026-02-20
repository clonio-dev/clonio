<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('logging.log_sql_queries')) {
            DB::listen(function (QueryExecuted $query): void {
                Log::debug(sprintf(
                    '[SQL] %s [%s] (%.1fms)',
                    $query->sql,
                    implode(', ', $query->bindings),
                    $query->time,
                ));
            });
        }
    }
}
