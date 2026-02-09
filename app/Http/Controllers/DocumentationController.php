<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Repositories\DocumentationRepository;
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
        private readonly DocumentationService $service,
        private readonly DocumentationRepository $repository,
    ) {}

    /**
     * Redirect to the first available documentation page.
     */
    public function index(): RedirectResponse
    {
        $firstPage = $this->service->getFirstPage();

        abort_if($firstPage === null, 404);

        return to_route('docs.show', $firstPage);
    }

    /**
     * Display a documentation page.
     */
    public function show(string $chapter, string $page): Response
    {
        $pageData = $this->service->getPage($chapter, $page);

        abort_if($pageData === null, 404);

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
     * Serve a media file from the docs directory.
     */
    public function image(string $path): BinaryFileResponse
    {
        $filePath = $this->repository->resolveFilePath($path);

        abort_if($filePath === null, 404);

        $docsPath = (string) realpath(config('docs.path'));
        $realFilePath = (string) realpath($filePath);

        abort_unless(str_starts_with($realFilePath, $docsPath), 404);

        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'mp4', 'webm', 'ogg'];
        $extension = mb_strtolower(pathinfo($realFilePath, PATHINFO_EXTENSION));

        abort_unless(in_array($extension, $allowedExtensions, true), 404);

        return response()->file($realFilePath);
    }
}
