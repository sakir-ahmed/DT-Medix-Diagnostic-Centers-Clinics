<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Demo-only routes (one-click demo logins). Refuses everything unless
 * IS_DEMO is on and the environment is not production.
 */
class EnsureDemoMode
{
    public function __construct(private readonly Application $app) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(config('app.demo') && ! $this->app->isProduction(), 404);

        return $next($request);
    }
}
