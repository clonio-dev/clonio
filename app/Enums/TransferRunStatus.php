<?php

declare(strict_types=1);

namespace App\Enums;

enum TransferRunStatus: string
{
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::QUEUED => 'queued',
            self::PROCESSING => 'processing',
            self::COMPLETED => 'completed',
            self::FAILED => 'failed',
            self::CANCELLED => 'cancelled by user',
            default => $this->value,
        };
    }
}
