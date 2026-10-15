<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unknown tenant subdomains return 404 on every web route.
 */
class EnsureKnownHost
{
    public function handle(Request $request, Closure $next): Response
    {
        ResolveTenant::tenantFromHost($request->getHost());

        return $next($request);
    }
}
