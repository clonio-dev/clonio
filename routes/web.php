<?php

declare(strict_types=1);

use App\Http\Controllers\TransferRunController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', fn () => view('home', [
    'canRegister' => Features::enabled(Features::registration()),
]))->name('home');

Route::get('dashboard', [TransferRunController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

require __DIR__ . '/settings.php';
require __DIR__ . '/application.php';

require __DIR__.'/static-pages.php';
