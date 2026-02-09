<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\DocChapter;
use App\Data\DocPage;
use App\Services\StaticPageFileService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class DocumentationRepository
{
    public function __construct(
        private StaticPageFileService $fileService,
    ) {}

    /**
     * Scan the docs folder and return all chapters with their pages.
     *
     * @return Collection<int, DocChapter>
     */
    public function getChapters(): Collection
    {
        $docsPath = config('docs.path');

        if (! is_dir($docsPath)) {
            return collect();
        }

        $directories = collect(scandir($docsPath))
            ->filter(fn (string $entry): bool => is_dir($docsPath . '/' . $entry) && preg_match('/^\d+-/', $entry) === 1)
            ->sort()
            ->values();

        return $directories->map(function (string $dirName) use ($docsPath): DocChapter {
            $chapterSlug = $this->removeNumberPrefix($dirName);
            $chapterTitle = Str::title(str_replace('-', ' ', $chapterSlug));
            $pages = $this->getPagesForChapter($docsPath . '/' . $dirName);

            return new DocChapter(
                title: $chapterTitle,
                slug: $chapterSlug,
                pages: $pages,
            );
        });
    }

    /**
     * Load a specific page from a chapter folder.
     */
    public function getPage(string $chapterSlug, string $pageSlug): ?DocPage
    {
        $docsPath = config('docs.path');
        $chapterDir = $this->findDirectoryBySlug($docsPath, $chapterSlug);

        if ($chapterDir === null) {
            return null;
        }

        $filePath = $this->findFileBySlug($chapterDir, $pageSlug);

        if ($filePath === null) {
            return null;
        }

        return $this->parsePageFile($filePath, $pageSlug);
    }

    /**
     * Search across all pages' titles, introductions, and content.
     *
     * @return Collection<int, array{title: string, introduction: string, chapter: string, page: string}>
     */
    public function search(string $query): Collection
    {
        $query = mb_strtolower($query);
        $results = collect();

        foreach ($this->getChapters() as $chapter) {
            foreach ($chapter->pages as $page) {
                $matchesTitle = str_contains(mb_strtolower($page->title), $query);
                $matchesIntro = str_contains(mb_strtolower($page->introduction), $query);
                $matchesContent = str_contains(mb_strtolower($page->content), $query);

                if ($matchesTitle || $matchesIntro || $matchesContent) {
                    $results->push([
                        'title' => $page->title,
                        'introduction' => $page->introduction,
                        'chapterTitle' => $chapter->title,
                        'chapter' => $chapter->slug,
                        'page' => $page->slug,
                    ]);
                }
            }
        }

        return $results;
    }

    /**
     * Return adjacent (previous/next) pages for navigation.
     *
     * @return array{previous: array{title: string, chapter: string, page: string}|null, next: array{title: string, chapter: string, page: string}|null}
     */
    public function getAdjacentPages(string $chapterSlug, string $pageSlug): array
    {
        $flatPages = [];

        foreach ($this->getChapters() as $chapter) {
            foreach ($chapter->pages as $page) {
                $flatPages[] = [
                    'title' => $page->title,
                    'chapter' => $chapter->slug,
                    'page' => $page->slug,
                ];
            }
        }

        $currentIndex = null;

        foreach ($flatPages as $index => $entry) {
            if ($entry['chapter'] === $chapterSlug && $entry['page'] === $pageSlug) {
                $currentIndex = $index;
                break;
            }
        }

        return [
            'previous' => $currentIndex !== null && $currentIndex > 0
                ? $flatPages[$currentIndex - 1]
                : null,
            'next' => $currentIndex !== null && $currentIndex < count($flatPages) - 1
                ? $flatPages[$currentIndex + 1]
                : null,
        ];
    }

    /**
     * Return slugs of the first available page.
     *
     * @return array{chapter: string, page: string}|null
     */
    public function getFirstPage(): ?array
    {
        $chapters = $this->getChapters();

        if ($chapters->isEmpty()) {
            return null;
        }

        $firstChapter = $chapters->first();

        if ($firstChapter->pages->isEmpty()) {
            return null;
        }

        $firstPage = $firstChapter->pages->first();

        return [
            'chapter' => $firstChapter->slug,
            'page' => $firstPage->slug,
        ];
    }

    /**
     * Resolve a slug-based file path to the real filesystem path.
     *
     * Converts "getting-started/cloning-flow.svg" to "/full/path/docs/0-getting-started/cloning-flow.svg".
     */
    public function resolveFilePath(string $relativePath): ?string
    {
        $docsPath = config('docs.path');
        $parts = explode('/', $relativePath, 2);

        if (count($parts) < 2) {
            return null;
        }

        $chapterDir = $this->findDirectoryBySlug($docsPath, $parts[0]);

        if ($chapterDir === null) {
            return null;
        }

        $filePath = $chapterDir . '/' . $parts[1];

        return file_exists($filePath) ? $filePath : null;
    }

    /**
     * Get pages for a chapter directory, ordered by number prefix.
     *
     * @return Collection<int, DocPage>
     */
    private function getPagesForChapter(string $chapterPath): Collection
    {
        if (! is_dir($chapterPath)) {
            return collect();
        }

        return collect(scandir($chapterPath))
            ->filter(fn (string $file): bool => str_ends_with($file, '.md'))
            ->sort()
            ->values()
            ->map(fn (string $file): DocPage => $this->parsePageFile(
                $chapterPath . '/' . $file,
                $this->removeNumberPrefixAndExtension($file),
            ));
    }

    /**
     * Parse a markdown file into a DocPage.
     */
    private function parsePageFile(string $filePath, string $slug): DocPage
    {
        $staticPage = $this->fileService->parseFile($filePath);

        return new DocPage(
            title: (string) $staticPage->get('title', Str::title(str_replace('-', ' ', $slug))),
            introduction: (string) $staticPage->get('introduction', ''),
            slug: $slug,
            content: (string) $staticPage->get('content', ''),
        );
    }

    /**
     * Find a numbered directory matching the given slug.
     */
    private function findDirectoryBySlug(string $basePath, string $slug): ?string
    {
        if (! is_dir($basePath)) {
            return null;
        }

        foreach (scandir($basePath) as $entry) {
            if (! is_dir($basePath . '/' . $entry)) {
                continue;
            }

            if ($this->removeNumberPrefix($entry) === $slug) {
                return $basePath . '/' . $entry;
            }
        }

        return null;
    }

    /**
     * Find a markdown file matching the given slug within a directory.
     */
    private function findFileBySlug(string $dirPath, string $slug): ?string
    {
        foreach (scandir($dirPath) as $file) {
            if (! str_ends_with($file, '.md')) {
                continue;
            }

            if ($this->removeNumberPrefixAndExtension($file) === $slug) {
                return $dirPath . '/' . $file;
            }
        }

        return null;
    }

    /**
     * Remove the number prefix (e.g., "0-" from "0-getting-started").
     */
    private function removeNumberPrefix(string $name): string
    {
        return preg_replace('/^\d+-/', '', $name);
    }

    /**
     * Remove the number prefix and .md extension (e.g., "01-introduction.md" -> "introduction").
     */
    private function removeNumberPrefixAndExtension(string $filename): string
    {
        return $this->removeNumberPrefix(pathinfo($filename, PATHINFO_FILENAME));
    }
}
