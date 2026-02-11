<?php

declare(strict_types=1);

use App\Http\Controllers\Api\TriggerCloningController;
use Illuminate\Support\Facades\Route;

Route::post('/trigger/{token}', TriggerCloningController::class)
    ->name('api.trigger-cloning');
