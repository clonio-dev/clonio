<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    include __DIR__ . '/application/connections.php';
    include __DIR__ . '/application/transfers.php';
    include __DIR__ . '/application/clonings.php';
});
