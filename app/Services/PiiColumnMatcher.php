<?php

declare(strict_types=1);

namespace App\Services;

class PiiColumnMatcher
{
    /** @var array<int, array{pattern: string, name: string, transformation: array{strategy: string, options: array<string, mixed>}}> */
    private readonly array $patterns;

    public function __construct()
    {
        $this->patterns = config('pii-columns.patterns', []);
    }

    /**
     * Match a column name against PII patterns.
     *
     * @return array{name: string, transformation: array{strategy: string, options: array<string, mixed>}}|null
     */
    public function match(string $columnName): ?array
    {
        foreach ($this->patterns as $entry) {
            if (preg_match($entry['pattern'], $columnName)) {
                return [
                    'name' => $entry['name'],
                    'transformation' => $entry['transformation'],
                ];
            }
        }

        return null;
    }

    /**
     * Match all columns for a table and return PII matches.
     *
     * @param  array<int, string>  $columnNames
     * @return array<string, array{name: string, transformation: array{strategy: string, options: array<string, mixed>}}>
     */
    public function matchColumns(array $columnNames): array
    {
        $matches = [];

        foreach ($columnNames as $columnName) {
            $match = $this->match($columnName);
            if ($match !== null) {
                $matches[$columnName] = $match;
            }
        }

        return $matches;
    }
}
