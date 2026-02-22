<?php

declare(strict_types=1);

use App\Http\Controllers\CloningRunController;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

if (config('clonio.mode') === 'marketing') {
    Route::get('/', fn (): Factory|View => view('home', [
        'canRegister' => false,
    ]))->name('home');
} else {
    Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

    Route::get('dashboard', [CloningRunController::class, 'dashboard'])
        ->middleware(['auth', 'verified'])
        ->name('dashboard');

    require __DIR__ . '/settings.php';
    require __DIR__ . '/application.php';
}

Route::get('/audit/{token}', [CloningRunController::class, 'publicAuditLog'])
    ->name('public.audit-log');

require __DIR__ . '/static-pages.php';
