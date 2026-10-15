<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant from the host ("{slug}.{base_domain}" or a custom
 * domain) or, on a central host, from the authenticated user. Never from
 * client input. Also restricts branch access for non-owner users.
 */
class ResolveTenant
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = self::tenantFromHost($request->getHost());

        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null && $user->tenant_id !== null) {
            if ($tenant !== null && $tenant->id !== $user->tenant_id) {
                abort(404);
            }

            $tenant ??= Tenant::query()->find($user->tenant_id);
        }

        if ($tenant === null) {
            if ($user !== null && $user->tenant_id === null && ! $request->expectsJson()) {
                return redirect()->route('platform.tenants.index');
            }

            abort(404);
        }

        $this->context->set($tenant);

        if ($user !== null) {
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $branchIds = $user->accessibleBranchIds();
            $this->context->restrictBranches($branchIds);
            $this->context->setCurrentBranch($this->currentBranch($request, $branchIds, $tenant));
        }

        return $next($request);
    }

    public static function tenantFromHost(string $host): ?Tenant
    {
        $host = Str::lower($host);

        if (in_array($host, config('tenancy.central_domains', []), true)) {
            return null;
        }

        $base = Str::lower((string) config('tenancy.base_domain'));

        if ($base !== '' && Str::endsWith($host, '.'.$base)) {
            $slug = Str::beforeLast($host, '.'.$base);

            return Tenant::query()->where('slug', $slug)->first() ?? abort(404);
        }

        return Tenant::query()->where('domain', $host)->first();
    }

    /**
     * @param  list<int>|null  $branchIds
     */
    private function currentBranch(Request $request, ?array $branchIds, Tenant $tenant): ?int
    {
        $sessionBranch = $request->hasSession() ? $request->session()->get('branch_id') : null;

        $allowed = $branchIds ?? $tenant->branches()->where('is_active', true)->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();

        if ($sessionBranch !== null && in_array((int) $sessionBranch, $allowed, true)) {
            return (int) $sessionBranch;
        }

        return $allowed[0] ?? null;
    }
}
