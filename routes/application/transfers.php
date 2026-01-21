<?php

declare(strict_types=1);

use App\Http\Controllers\TransferRunController;
use Illuminate\Support\Facades\Route;

Route::prefix('transfers')
    ->name('transfers.')
    ->group(function (): void {
        Route::get('/create', [TransferRunController::class, 'create'])
            ->name('transfers.create');
        Route::post('/', [TransferRunController::class, 'store'])
            ->name('transfers.store');

        // Show single run detail
        Route::get('/', [TransferRunController::class, 'index'])
            ->name('index');

        // Show single run detail
        Route::get('/{run}', [TransferRunController::class, 'show'])
            ->name('show');

        // Cancel running transfer
        Route::post('/{run}/cancel', [TransferRunController::class, 'cancel'])
            ->name('cancel');

        // Retry failed transfer
        Route::post('/{run}/retry', [TransferRunController::class, 'retry'])
            ->name('retry');

        // Export logs as JSON download
        Route::get('/{run}/logs/export', [TransferRunController::class, 'exportLogs'])
            ->name('logs.export');
    });
