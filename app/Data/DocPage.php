<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Represents a single documentation page parsed from a markdown file.
 */
final readonly class DocPage
{
    public function __construct(
        public string $title,
        public string $introduction,
        public string $slug,
        public string $content,
    ) {}
}
