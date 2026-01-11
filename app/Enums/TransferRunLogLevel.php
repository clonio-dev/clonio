<?php

declare(strict_types=1);

namespace App\Enums;

enum TransferRunLogLevel: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';

    public function getLabel(): string
    {
        return match ($this) {
            self::INFO => 'info',
            self::WARNING => 'warning',
            self::ERROR => 'error',
            default => $this->value,
        };
    }
}
