<?php

declare(strict_types=1);

return [
    'audit_secret' => env('AUDIT_SECRET'),

    /**
     * We have 2 application modes: `marketing` and `application`.
     *
     * The application mode presents the application with auth and clonings stuff.
     * The marketing mode presents just the marketing home page.
     *
     * Both provide the technical documentation.
     */
    'mode' => env('APP_MODE', 'application'),
];
