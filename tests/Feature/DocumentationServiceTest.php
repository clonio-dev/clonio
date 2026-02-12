<?php

declare(strict_types=1);

use App\Data\DocHeading;
use App\Services\DocumentationService;

beforeEach(function (): void {
    config(['docs.path' => base_path('docs')]);
});

it('returns navigation with chapters and pages', function (): void {
    $service = resolve(DocumentationService::class);
    $navigation = $service->getNavigation();

    expect($navigation)->toHaveCount(5)
        ->and($navigation->first())->toHaveKeys(['title', 'slug', 'pages'])
        ->and($navigation->first()['pages'])->not->toBeEmpty()
        ->and($navigation->first()['pages'][0])->toHaveKeys(['title', 'slug']);
});

it('navigation does not include raw markdown content', function (): void {
    $service = resolve(DocumentationService::class);
    $navigation = $service->getNavigation();

    $firstPage = $navigation->first()['pages'][0];

    expect($firstPage)->not->toHaveKey('content')
        ->and($firstPage)->not->toHaveKey('introduction');
});

it('returns page data with HTML content and headings', function (): void {
    $service = resolve(DocumentationService::class);
    $page = $service->getPage('getting-started', 'introduction');

    expect($page)->not->toBeNull()
        ->and($page['title'])->toBe('Introduction')
        ->and($page['htmlContent'])->not->toContain('<h1>')
        ->and($page['headings'])->not->toBeEmpty()
        ->and($page['headings'][0])->toBeInstanceOf(DocHeading::class);
});

it('adds IDs to h2 and h3 headings', function (): void {
    $service = resolve(DocumentationService::class);
    $page = $service->getPage('getting-started', 'introduction');

    expect($page['htmlContent'])->toContain('id="the-problem"')
        ->and($page['htmlContent'])->toContain('id="what-clonio-does"');
});

it('extracts headings for table of contents', function (): void {
    $service = resolve(DocumentationService::class);
    $page = $service->getPage('getting-started', 'introduction');

    $headingSlugs = array_map(fn (DocHeading $h): string => $h->slug, $page['headings']);

    expect($headingSlugs)->toContain('the-problem')
        ->and($headingSlugs)->toContain('what-clonio-does')
        ->and($headingSlugs)->toContain('how-it-works');
});

it('returns null for non-existent page', function (): void {
    $service = resolve(DocumentationService::class);

    expect($service->getPage('nonexistent', 'page'))->toBeNull();
});

it('strips the first h1 from HTML content', function (): void {
    $service = resolve(DocumentationService::class);
    $page = $service->getPage('getting-started', 'introduction');

    expect($page['htmlContent'])->not->toContain('<h1>')
        ->and($page['htmlContent'])->toContain('<h2');
});

it('includes previous and next page navigation data', function (): void {
    $service = resolve(DocumentationService::class);
    $page = $service->getPage('getting-started', 'installation');

    expect($page)->toHaveKeys(['previousPage', 'nextPage'])
        ->and($page['previousPage'])->not->toBeNull()
        ->and($page['previousPage'])->toHaveKeys(['title', 'url'])
        ->and($page['previousPage']['url'])->toContain('/docs/getting-started/introduction')
        ->and($page['nextPage'])->not->toBeNull()
        ->and($page['nextPage'])->toHaveKeys(['title', 'url'])
        ->and($page['nextPage']['url'])->toContain('/docs/connections/managing-connections');
});

it('returns null previousPage for the first page', function (): void {
    $service = resolve(DocumentationService::class);
    $page = $service->getPage('getting-started', 'introduction');

    expect($page['previousPage'])->toBeNull()
        ->and($page['nextPage'])->not->toBeNull();
});

it('searches documentation and includes URLs', function (): void {
    $service = resolve(DocumentationService::class);
    $results = $service->search('installation');

    expect($results)->not->toBeEmpty()
        ->and($results->first())->toHaveKeys(['title', 'introduction', 'url', 'chapter', 'page'])
        ->and($results->first()['url'])->toContain('/docs/');
});
