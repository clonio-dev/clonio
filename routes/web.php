<?php

declare(strict_types=1);

use App\Http\Controllers\CloningRunController;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

Route::get('/', function (): Factory|View|Response {
    if (config('clonio.mode') === 'marketing') {
        return view('home', ['canRegister' => Features::enabled(Features::registration())]);
    }

    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [CloningRunController::class, 'dashboard'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/audit/{token}', [CloningRunController::class, 'publicAuditLog'])
    ->name('public.audit-log');

require __DIR__ . '/settings.php';
require __DIR__ . '/application.php';
require __DIR__ . '/static-pages.php';
