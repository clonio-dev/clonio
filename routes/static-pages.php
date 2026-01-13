<?php

declare(strict_types=1);

use App\Services\StaticPageFileService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;

collect([
    'imprint' => resource_path('markdown/imprint.md'),
    'policy' => resource_path('markdown/policy.md'),
    'terms' => resource_path('markdown/terms.md'),
    'impressum' => resource_path('markdown/imprint.de.md'),
    'datenschutz' => resource_path('markdown/policy.de.md'),
    'agb' => resource_path('markdown/terms.de.md'),
])->each(function ($markdownFile, $path): void {
        Route::get($path, function (StaticPageFileService $pageFileService) use ($path, $markdownFile) {
            try {
                $staticPage = $pageFileService->parseFile($markdownFile);
            } catch (Throwable) {
                abort(404);
            }

            return Inertia::render('StaticPage', [
                'title' => $staticPage->get('title', Str::title($path)),
                'content' => $staticPage->getHtml(),
            ]);
        })->name('static.'.$path);
    });
