<?php

declare(strict_types=1);

namespace App\Data;

final readonly class WebhookConfigData
{
    /**
     * @param  array<string, string>  $headers
     */
    public function __construct(
        public bool $enabled = false,
        public string $url = '',
        public string $method = 'POST',
        public array $headers = [],
        public string $secret = '',
    ) {}
}
