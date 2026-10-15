<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\DomainException;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Suspended tenants are blocked; read-only tenants may only read, sign out
 * and export their data.
 */
class EnsureTenantIsActive
{
    /**
     * Routes that stay available while the tenant is read-only.
     *
     * @var list<string>
     */
    private const READ_ONLY_ALLOWED = ['logout', 'locale.update', 'admin.branch.switch', 'admin.export.store', 'api.v1.auth.logout'];

    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->context->tenant();

        if ($tenant === null) {
            return $next($request);
        }

        if ($tenant->isSuspended() && ! $request->routeIs('logout', 'api.v1.auth.logout')) {
            throw DomainException::make('TENANT_SUSPENDED', 403);
        }

        if ($tenant->isReadOnly() && ! $request->isMethodSafe() && ! $request->routeIs(...self::READ_ONLY_ALLOWED)) {
            throw DomainException::make('TENANT_READ_ONLY', 403);
        }

        return $next($request);
    }
}
