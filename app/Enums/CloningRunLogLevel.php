<?php

declare(strict_types=1);

namespace App\Enums;

enum CloningRunLogLevel: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
    case SUCCESS = 'success';

    public function getLabel(): string
    {
        return match ($this) {
            self::INFO => 'info',
            self::WARNING => 'warning',
            self::ERROR => 'error',
            self::SUCCESS => 'success',
        };
    }
}
