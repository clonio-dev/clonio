<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ColumnMutationDataOptions
{
    /**
     * @param  array<int, mixed>  $fakerMethodArguments
     */
    public function __construct(
        /** FakeOptions */
        public string $fakerMethod = 'word',
        public array $fakerMethodArguments = [],
        /** MaskOptions */
        public int $visibleChars = 2,
        public string $maskChar = '*',
        public bool $preserveFormat = false,
        /** HashOptions */
        public string $algorithm = 'sha256',
        public string $salt = '',
        /** StaticOptions */
        public mixed $value = null,
    ) {}
}
