<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Branch routes 404 unless the branch belongs to the tenant and the user
 * may work in it. The resolved branch becomes the current branch.
 */
class EnsureBranchAccess
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $parameter = $request->route('branch');
        $branch = $parameter instanceof Branch
            ? $parameter
            : Branch::query()->whereKey($parameter)->first();

        if ($branch === null || ! $branch->is_active) {
            abort(404);
        }

        $allowed = $this->context->branchIds();

        if ($allowed !== null && ! in_array($branch->id, $allowed, true)) {
            abort(404);
        }

        $this->context->setCurrentBranch($branch->id);
        $request->route()->setParameter('branch', $branch);

        return $next($request);
    }
}
