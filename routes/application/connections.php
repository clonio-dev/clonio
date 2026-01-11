<?php

declare(strict_types=1);

use App\Http\Controllers\DatabaseConnections\CreateController;
use App\Http\Controllers\DatabaseConnections\IndexController;
use Illuminate\Support\Facades\Route;

Route::prefix('connections')
    ->name('connections.')
    ->group(function (): void {

        Route::get('/', IndexController::class)
            ->name('index');

        Route::post('/', CreateController::class)
            ->name('store');

    });
