<?php

declare(strict_types=1);

use App\Http\Controllers\CloningRunController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', fn (): Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View => view('home', [
    'canRegister' => Features::enabled(Features::registration()),
]))->name('home');

Route::get('dashboard', [CloningRunController::class, 'dashboard'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/audit/{token}', [CloningRunController::class, 'publicAuditLog'])
    ->name('public.audit-log');

require __DIR__ . '/settings.php';
require __DIR__ . '/application.php';

require __DIR__ . '/docs.php';
require __DIR__ . '/static-pages.php';
