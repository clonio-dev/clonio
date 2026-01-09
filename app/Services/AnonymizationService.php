<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ColumnMutationData;
use App\Data\ColumnMutationStrategyEnum;
use App\Data\TableAnonymizationOptionsData;

final readonly class AnonymizationService
{
    /**
     * Apply anonymization to a record based on table configuration.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    public function anonymizeRecord(array $record, ?TableAnonymizationOptionsData $tableOptions): array
    {
        if (! $tableOptions instanceof TableAnonymizationOptionsData) {
            return $record;
        }

        $columnMutationsMap = $tableOptions->getColumnMutationsMap();

        foreach ($columnMutationsMap as $columnName => $mutationData) {
            if (! array_key_exists($columnName, $record)) {
                continue;
            }

            $record[$columnName] = $this->applyMutation($record[$columnName], $mutationData);
        }

        return $record;
    }

    private function applyMutation(mixed $value, ColumnMutationData $mutationData): mixed
    {
        return match ($mutationData->strategy) {
            ColumnMutationStrategyEnum::FAKE => $this->applyFakeStrategy($mutationData),
            ColumnMutationStrategyEnum::MASK => $this->applyMaskStrategy($value, $mutationData),
            ColumnMutationStrategyEnum::HASH => $this->applyHashStrategy($value, $mutationData),
            ColumnMutationStrategyEnum::NULL => null,
            ColumnMutationStrategyEnum::STATIC => $this->applyStaticStrategy($mutationData),
        };
    }

    private function applyFakeStrategy(ColumnMutationData $mutationData): mixed
    {
        $fakeMethod = $mutationData->options->fakerMethod;
        $fakeArguments = $mutationData->options->fakerMethodArguments;

        return fake()->{$fakeMethod}(...$fakeArguments);
    }

    private function applyMaskStrategy(mixed $value, ColumnMutationData $mutationData): string
    {
        if ($value === null) {
            return '';
        }

        /** @phpstan-ignore cast.string */
        $valueStr = (string) $value;
        $visibleChars = $mutationData->options->visibleChars;
        $maskChar = $mutationData->options->maskChar;
        $preserveFormat = $mutationData->options->preserveFormat;

        if ($preserveFormat && str_contains($valueStr, '@')) {
            return $this->maskEmail($valueStr, $visibleChars, $maskChar);
        }

        $length = mb_strlen($valueStr);
        if ($length <= $visibleChars) {
            return str_repeat($maskChar, $length);
        }

        $visible = mb_substr($valueStr, 0, $visibleChars);
        $masked = str_repeat($maskChar, $length - $visibleChars);

        return $visible . $masked;
    }

    private function maskEmail(string $email, int $visibleChars, string $maskChar): string
    {
        [$localPart, $domain] = explode('@', $email, 2);

        $localLength = mb_strlen($localPart);
        if ($localLength <= $visibleChars) {
            $maskedLocal = str_repeat($maskChar, $localLength);
        } else {
            $visible = mb_substr($localPart, 0, $visibleChars);
            $maskedLocal = $visible . str_repeat($maskChar, $localLength - $visibleChars);
        }

        return $maskedLocal . '@' . $domain;
    }

    private function applyHashStrategy(mixed $value, ColumnMutationData $mutationData): string
    {
        if ($value === null) {
            return '';
        }

        /** @phpstan-ignore cast.string */
        $value = (string) $value;
        $algorithm = $mutationData->options->algorithm;
        $salt = $mutationData->options->salt;

        return hash($algorithm, $salt . $value);
    }

    private function applyStaticStrategy(ColumnMutationData $mutationData): mixed
    {
        return $mutationData->options->value;
    }
}
