<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DocumentationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentationController extends Controller
{
    public function __construct(
        private DocumentationService $service,
    ) {}

    /**
     * Redirect to the first available documentation page.
     */
    public function index(): RedirectResponse
    {
        $firstPage = $this->service->getFirstPage();

        if ($firstPage === null) {
            abort(404);
        }

        return to_route('docs.show', $firstPage);
    }

    /**
     * Display a documentation page.
     */
    public function show(string $chapter, string $page): Response
    {
        $pageData = $this->service->getPage($chapter, $page);

        if ($pageData === null) {
            abort(404);
        }

        return Inertia::render('docs/Show', [
            'page' => $pageData,
            'chapters' => $this->service->getNavigation(),
            'currentChapter' => $chapter,
            'currentPage' => $page,
        ]);
    }

    /**
     * Search documentation and display results.
     */
    public function search(Request $request): Response
    {
        $query = $request->string('q')->toString();
        $results = $query !== '' ? $this->service->search($query) : collect();

        return Inertia::render('docs/Search', [
            'query' => $query,
            'results' => $results,
            'chapters' => $this->service->getNavigation(),
        ]);
    }

    /**
     * Search documentation and return JSON results for live search.
     */
    public function searchJson(Request $request): JsonResponse
    {
        $query = $request->string('q')->toString();
        $results = $query !== '' ? $this->service->search($query) : collect();

        return response()->json(['results' => $results->values()]);
    }

    /**
     * Serve an image from the docs directory.
     */
    public function image(string $path): BinaryFileResponse
    {
        $docsPath = config('docs.path');
        $filePath = realpath($docsPath . '/' . $path);

        if (
            $filePath === false
            || ! str_starts_with($filePath, (string) realpath($docsPath))
            || ! file_exists($filePath)
        ) {
            abort(404);
        }

        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'];
        $extension = mb_strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (! in_array($extension, $allowedExtensions, true)) {
            abort(404);
        }

        return response()->file($filePath);
    }
}
