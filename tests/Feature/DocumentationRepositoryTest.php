<?php

declare(strict_types=1);

use App\Data\DocChapter;
use App\Data\DocPage;
use App\Repositories\DocumentationRepository;

beforeEach(function (): void {
    config(['docs.path' => base_path('docs')]);
});

it('returns chapters ordered by number prefix', function (): void {
    $repository = resolve(DocumentationRepository::class);
    $chapters = $repository->getChapters();

    expect($chapters)->toHaveCount(5)
        ->and($chapters->first())->toBeInstanceOf(DocChapter::class)
        ->and($chapters->first()->slug)->toBe('getting-started')
        ->and($chapters->first()->title)->toBe('Getting Started')
        ->and($chapters->get(1)->slug)->toBe('connections')
        ->and($chapters->get(2)->slug)->toBe('clonings')
        ->and($chapters->get(3)->slug)->toBe('cloning-runs')
        ->and($chapters->last()->slug)->toBe('settings');
});

it('returns pages within chapters ordered by number prefix', function (): void {
    $repository = resolve(DocumentationRepository::class);
    $chapters = $repository->getChapters();

    $gettingStarted = $chapters->first();
    expect($gettingStarted->pages)->toHaveCount(2)
        ->and($gettingStarted->pages->first()->slug)->toBe('introduction')
        ->and($gettingStarted->pages->last()->slug)->toBe('installation');
});

it('returns a specific page by chapter and page slug', function (): void {
    $repository = resolve(DocumentationRepository::class);
    $page = $repository->getPage('getting-started', 'introduction');

    expect($page)->toBeInstanceOf(DocPage::class)
        ->and($page->title)->toBe('Introduction')
        ->and($page->introduction)->toContain('Clonio')
        ->and($page->slug)->toBe('introduction')
        ->and($page->content)->toContain('# Introduction');
});

it('returns null for non-existent chapter', function (): void {
    $repository = resolve(DocumentationRepository::class);

    expect($repository->getPage('non-existent', 'page'))->toBeNull();
});

it('returns null for non-existent page', function (): void {
    $repository = resolve(DocumentationRepository::class);

    expect($repository->getPage('getting-started', 'non-existent'))->toBeNull();
});

it('searches across pages', function (): void {
    $repository = resolve(DocumentationRepository::class);
    $results = $repository->search('anonymization');

    expect($results)->not->toBeEmpty()
        ->and($results->pluck('title'))->toContain('Anonymization');
});

it('returns empty collection for no search matches', function (): void {
    $repository = resolve(DocumentationRepository::class);

    expect($repository->search('xyznonexistent123'))->toBeEmpty();
});

it('returns first page slugs', function (): void {
    $repository = resolve(DocumentationRepository::class);
    $first = $repository->getFirstPage();

    expect($first)->toBe([
        'chapter' => 'getting-started',
        'page' => 'introduction',
    ]);
});

it('returns no previous page for the first page', function (): void {
    $repository = resolve(DocumentationRepository::class);
    $adjacent = $repository->getAdjacentPages('getting-started', 'introduction');

    expect($adjacent['previous'])->toBeNull()
        ->and($adjacent['next'])->not->toBeNull()
        ->and($adjacent['next']['page'])->toBe('installation');
});

it('returns no next page for the last page', function (): void {
    $repository = resolve(DocumentationRepository::class);
    $adjacent = $repository->getAdjacentPages('settings', 'profile-and-security');

    expect($adjacent['next'])->toBeNull()
        ->and($adjacent['previous'])->not->toBeNull();
});

it('returns both previous and next for a middle page', function (): void {
    $repository = resolve(DocumentationRepository::class);
    $adjacent = $repository->getAdjacentPages('getting-started', 'installation');

    expect($adjacent['previous'])->not->toBeNull()
        ->and($adjacent['previous']['page'])->toBe('introduction')
        ->and($adjacent['previous']['chapter'])->toBe('getting-started')
        ->and($adjacent['next'])->not->toBeNull()
        ->and($adjacent['next']['page'])->toBe('managing-connections')
        ->and($adjacent['next']['chapter'])->toBe('connections');
});

it('crosses chapter boundaries for adjacent pages', function (): void {
    $repository = resolve(DocumentationRepository::class);
    $adjacent = $repository->getAdjacentPages('connections', 'managing-connections');

    expect($adjacent['previous'])->not->toBeNull()
        ->and($adjacent['previous']['chapter'])->toBe('getting-started')
        ->and($adjacent['previous']['page'])->toBe('installation')
        ->and($adjacent['next'])->not->toBeNull()
        ->and($adjacent['next']['chapter'])->toBe('connections')
        ->and($adjacent['next']['page'])->toBe('supported-databases');
});

it('returns empty collection when docs path does not exist', function (): void {
    config(['docs.path' => '/nonexistent/path']);

    $repository = resolve(DocumentationRepository::class);

    expect($repository->getChapters())->toBeEmpty()
        ->and($repository->getFirstPage())->toBeNull();
});
