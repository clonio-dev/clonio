<?php

declare(strict_types=1);

use App\Http\Controllers\CloningController;
use App\Http\Controllers\CloningRunController;
use Illuminate\Support\Facades\Route;

// Cloning configurations
Route::prefix('clonings')
    ->name('clonings.')
    ->group(function (): void {
        Route::get('/', [CloningController::class, 'index'])
            ->name('index');

        Route::get('/create', [CloningController::class, 'create'])
            ->name('create');

        Route::post('/validate-connections', [CloningController::class, 'validateConnections'])
            ->name('validate-connections');

        Route::post('/', [CloningController::class, 'store'])
            ->name('store');

        Route::get('/{cloning}', [CloningController::class, 'show'])
            ->name('show');

        Route::get('/{cloning}/edit', [CloningController::class, 'edit'])
            ->name('edit');

        Route::put('/{cloning}', [CloningController::class, 'update'])
            ->name('update');

        Route::delete('/{cloning}', [CloningController::class, 'destroy'])
            ->name('destroy');

        Route::post('/{cloning}/execute', [CloningController::class, 'execute'])
            ->name('execute');
    });

// Cloning runs
Route::prefix('cloning-runs')
    ->name('cloning-runs.')
    ->group(function (): void {
        Route::get('/', [CloningRunController::class, 'index'])
            ->name('index');

        Route::get('/{run}', [CloningRunController::class, 'show'])
            ->name('show');

        Route::post('/{run}/cancel', [CloningRunController::class, 'cancel'])
            ->name('cancel');

        Route::get('/{run}/logs/export', [CloningRunController::class, 'exportLogs'])
            ->name('logs.export');
    });
