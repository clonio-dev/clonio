<?php

declare(strict_types=1);

use App\Http\Controllers\DocumentationController;
use Illuminate\Support\Facades\Route;

Route::prefix('docs')->name('docs.')->group(function (): void {
    Route::get('/', [DocumentationController::class, 'index'])->name('index');
    Route::get('/search', [DocumentationController::class, 'search'])->name('search');
    Route::get('/search/json', [DocumentationController::class, 'searchJson'])->name('search.json');
    Route::get('/images/{path}', [DocumentationController::class, 'image'])->where('path', '.*')->name('image');
    Route::get('/{chapter}/{page}', [DocumentationController::class, 'show'])->name('show');
});
