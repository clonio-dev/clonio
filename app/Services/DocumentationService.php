<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\DocChapter;
use App\Data\DocHeading;
use App\Repositories\DocumentationRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class DocumentationService
{
    public function __construct(
        private DocumentationRepository $repository,
    ) {}

    /**
     * Get chapters with their pages for sidebar navigation.
     *
     * @return Collection<int, array{title: string, slug: string, pages: array<int, array{title: string, slug: string}>}>
     */
    public function getNavigation(): Collection
    {
        return $this->repository->getChapters()->map(fn (DocChapter $chapter): array => [
            'title' => $chapter->title,
            'slug' => $chapter->slug,
            'pages' => $chapter->pages->map(fn ($page): array => [
                'title' => $page->title,
                'slug' => $page->slug,
            ])->all(),
        ]);
    }

    /**
     * Get full page data including parsed HTML and table of contents.
     *
     * @return array{title: string, introduction: string, htmlContent: string, headings: array<int, DocHeading>, slug: string}|null
     */
    public function getPage(string $chapter, string $page): ?array
    {
        $docPage = $this->repository->getPage($chapter, $page);

        if (! $docPage instanceof \App\Data\DocPage) {
            return null;
        }

        $html = Str::markdown($docPage->content, [
            'allow_unsafe_links' => false,
            'html_input' => 'allow',
        ]);

        $html = preg_replace('/<h1>.*?<\/h1>/s', '', $html, 1);

        $html = $this->addHeadingIds($html);
        $html = $this->fixMediaPaths($html, $chapter);
        $html = $this->addNoProseToCodeBlocks($html);
        $headings = $this->extractHeadings($html);

        $adjacent = $this->repository->getAdjacentPages($chapter, $page);

        return [
            'title' => $docPage->title,
            'introduction' => $docPage->introduction,
            'htmlContent' => $html,
            'headings' => $headings,
            'slug' => $docPage->slug,
            'previousPage' => $adjacent['previous'] ? [
                'title' => $adjacent['previous']['title'],
                'url' => route('docs.show', ['chapter' => $adjacent['previous']['chapter'], 'page' => $adjacent['previous']['page']]),
            ] : null,
            'nextPage' => $adjacent['next'] ? [
                'title' => $adjacent['next']['title'],
                'url' => route('docs.show', ['chapter' => $adjacent['next']['chapter'], 'page' => $adjacent['next']['page']]),
            ] : null,
        ];
    }

    /**
     * Search across documentation and return results with URLs.
     *
     * @return Collection<int, array{title: string, introduction: string, chapterTitle: string, url: string, chapter: string, page: string}>
     */
    public function search(string $query): Collection
    {
        return $this->repository->search($query)->map(function (array $result): array {
            $result['url'] = route('docs.show', [
                'chapter' => $result['chapter'],
                'page' => $result['page'],
            ]);

            return $result;
        });
    }

    /**
     * Get slugs of the first available page.
     *
     * @return array{chapter: string, page: string}|null
     */
    public function getFirstPage(): ?array
    {
        return $this->repository->getFirstPage();
    }

    /**
     * Add slug-based IDs to h2 and h3 headings in HTML.
     */
    private function addHeadingIds(string $html): string
    {
        return (string) preg_replace_callback(
            '/<h([23])>(.*?)<\/h[23]>/s',
            function (array $matches): string {
                $level = $matches[1];
                $text = strip_tags($matches[2]);
                $slug = Str::slug($text);

                return '<h' . $level . ' id="' . $slug . '">' . $matches[2] . '</h' . $level . '>';
            },
            $html,
        );
    }

    /**
     * Fix relative src paths in img and source tags to use the docs media route.
     */
    private function fixMediaPaths(string $html, string $chapterSlug): string
    {
        return (string) preg_replace_callback(
            '/(<(?:img|source)\s[^>]*?)src="([^"]*?)"([^>]*?>)/i',
            function (array $matches) use ($chapterSlug): string {
                $src = $matches[2];

                if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://') || str_starts_with($src, '/')) {
                    return $matches[0];
                }

                $newSrc = route('docs.image', ['path' => $chapterSlug . '/' . $src]);

                return $matches[1] . 'src="' . $newSrc . '"' . $matches[3];
            },
            $html,
        );
    }

    /**
     * Extract h2 and h3 headings from HTML for table of contents.
     *
     * @return array<int, DocHeading>
     */
    private function extractHeadings(string $html): array
    {
        $headings = [];

        preg_match_all('/<h([23])\s*id="([^"]*)"[^>]*>(.*?)<\/h[23]>/s', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $headings[] = new DocHeading(
                text: strip_tags($match[3]),
                slug: $match[2],
                level: (int) $match[1],
            );
        }

        return $headings;
    }

    /**
     * Adds the "not-prose" class to all code block elements within the provided HTML.
     *
     * Processes all `<code>` tags with a class attribute and appends the "not-prose" class to them.
     *
     * @param  string  $html  The HTML content to process.
     * @return string The modified HTML with updated code block classes.
     */
    private function addNoProseToCodeBlocks(string $html): string
    {
        preg_match_all('/<code class="([^"]*)">/s', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $html = str_replace($match[0], '<code class="' . $match[1] . ' not-prose">', $html);
        }

        return $html;
    }
}
