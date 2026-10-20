<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\User;
use App\Support\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts the signed-in user to the branches they are assigned to and
 * picks the branch they are working in (session choice, else the first
 * allowed branch). Owners and users with "branches.all" see every branch.
 */
class ResolveBranch
{
    public function __construct(private readonly BranchContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null) {
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $branchIds = $user->accessibleBranchIds();
            $this->context->restrictBranches($branchIds);
            $this->context->setCurrentBranch($this->currentBranch($request, $branchIds));
        }

        return $next($request);
    }

    /**
     * @param  list<int>|null  $branchIds
     */
    private function currentBranch(Request $request, ?array $branchIds): ?int
    {
        $sessionBranch = $request->hasSession() ? $request->session()->get('branch_id') : null;

        $allowed = $branchIds ?? Branch::query()->withoutGlobalScopes()->where('is_active', true)->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();

        if ($sessionBranch !== null && in_array((int) $sessionBranch, $allowed, true)) {
            return (int) $sessionBranch;
        }

        return $allowed[0] ?? null;
    }
}
