<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ApiTriggerConfigData
{
    public function __construct(
        public bool $enabled = false,
    ) {}
}
