<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockInMarketingMode
{
    /**
     * Routes that are not accessible in marketing mode.
     *
     * @var string[]
     */
    private const array BLOCKED_PATHS = [
        'login',
        'register',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        abort_if(config('clonio.mode') === 'marketing' && in_array($request->path(), self::BLOCKED_PATHS), 404);

        return $next($request);
    }
}
