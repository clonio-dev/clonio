<?php

declare(strict_types=1);

use App\Http\Controllers\DatabaseConnections\CreateController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DatabaseConnections\IndexController;

Route::prefix('connections')
    ->name('connections.')
    ->group(function () {

        Route::get('/', IndexController::class)
            ->name('index');

        Route::post('/', CreateController::class)
            ->name('store');

    });
