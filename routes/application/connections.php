<?php

declare(strict_types=1);

use App\Http\Controllers\DatabaseConnectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('connections')
    ->name('connections.')
    ->group(function (): void {

        Route::get('/', [DatabaseConnectionController::class, 'index'])
            ->name('index');

        Route::post('/', [DatabaseConnectionController::class, 'store'])
            ->name('store');

        Route::post('/-/test', [DatabaseConnectionController::class, 'testAllConnections']);
        Route::post('/{connection}/test', [DatabaseConnectionController::class, 'testConnection']);

        Route::put('/{connection}', [DatabaseConnectionController::class, 'update'])
            ->name('update');

        Route::delete('/{connection}', [DatabaseConnectionController::class, 'destroy'])
            ->name('destroy');
    });
