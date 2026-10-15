<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Support impersonation: a super-admin signs in as the tenant owner. The
 * session remembers the impersonator, a banner stays visible and both start
 * and end are written to the activity log.
 */
class ImpersonationController extends Controller
{
    public function store(Request $request, Tenant $tenant, TenantContext $context): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        $owner = $context->run($tenant, fn (): ?User => User::role(RoleName::Owner->value)->where('is_active', true)->orderBy('id')->first());

        abort_if($owner === null, 404);

        $context->run($tenant, fn () => activity('platform')->causedBy($admin)->performedOn($tenant)->withProperties(['as_user_id' => $owner->id])->log('impersonation.started'));

        Auth::guard('web')->login($owner);
        $request->session()->put('impersonator_id', $admin->id);
        $request->session()->put('last_activity_at', now()->timestamp);

        return redirect()->route('admin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $adminId = $request->session()->pull('impersonator_id');
        abort_if($adminId === null, 403);

        /** @var User|null $admin */
        $admin = User::query()->withoutGlobalScopes()->find($adminId);
        abort_if($admin === null, 403);

        activity('platform')->causedBy($admin)->withProperties(['as_user_id' => $request->user()?->getAuthIdentifier()])->log('impersonation.ended');
        $request->session()->forget('branch_id');

        Auth::guard('web')->login($admin);

        return redirect()->route('platform.tenants.index');
    }
}
