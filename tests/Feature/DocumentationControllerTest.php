<?php

declare(strict_types=1);

it('redirects /docs to the first page', function (): void {
    $this->get('/docs')
        ->assertRedirect('/docs/getting-started/introduction');
});

it('shows a documentation page', function (): void {
    $this->get('/docs/getting-started/introduction')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('docs/Show')
            ->has('page', fn ($page) => $page
                ->where('title', 'Introduction')
                ->where('slug', 'introduction')
                ->has('htmlContent')
                ->has('introduction')
                ->has('headings')
                ->has('previousPage')
                ->has('nextPage')
            )
            ->has('chapters')
            ->where('currentChapter', 'getting-started')
            ->where('currentPage', 'introduction')
        );
});

it('returns 404 for non-existent chapter', function (): void {
    $this->get('/docs/non-existent/page')
        ->assertNotFound();
});

it('returns 404 for non-existent page in valid chapter', function (): void {
    $this->get('/docs/getting-started/non-existent')
        ->assertNotFound();
});

it('shows search results page', function (): void {
    $this->get('/docs/search?q=anonymization')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('docs/Search')
            ->where('query', 'anonymization')
            ->has('results')
            ->has('chapters')
        );
});

it('shows empty search results for no query', function (): void {
    $this->get('/docs/search')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('docs/Search')
            ->where('query', '')
            ->where('results', [])
        );
});

it('returns JSON search results', function (): void {
    $this->getJson('/docs/search/json?q=anonymization')
        ->assertSuccessful()
        ->assertJsonStructure([
            'results' => [
                '*' => ['title', 'introduction', 'chapterTitle', 'url', 'chapter', 'page'],
            ],
        ]);
});

it('returns empty JSON search results for no query', function (): void {
    $this->getJson('/docs/search/json')
        ->assertSuccessful()
        ->assertJson(['results' => []]);
});

it('returns 404 for non-image files in docs images', function (): void {
    $this->get('/docs/images/getting-started/01-introduction.md')
        ->assertNotFound();
});
