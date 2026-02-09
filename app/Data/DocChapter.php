<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;

/**
 * Represents a documentation chapter (folder) containing pages.
 *
 * @property Collection<int, DocPage> $pages
 */
final readonly class DocChapter
{
    /**
     * @param  Collection<int, DocPage>  $pages
     */
    public function __construct(
        public string $title,
        public string $slug,
        public Collection $pages,
    ) {}
}
