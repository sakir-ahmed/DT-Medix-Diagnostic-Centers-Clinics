<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Staff\SaveStaffAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffRequest;
use App\Models\Branch;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(Request $request, TenantContext $context): Response
    {
        /** @var User $actor */
        $actor = $request->user();
        $branchIds = $context->branchIds();
        $search = trim((string) $request->query('q', ''));

        $staff = User::query()
            ->with(['roles', 'branches' => fn ($q) => $q->withoutGlobalScopes()])
            ->when($branchIds !== null, fn (Builder $q) => $q->whereHas('branches', fn (Builder $b) => $b->whereIn('branches.id', $branchIds)))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "{$search}%")->orWhere('email', 'like', "{$search}%")))
            ->when($request->query('role'), fn (Builder $q, $role) => $q->role((string) $role))
            ->orderBy('name')
            ->paginate((int) $request->query('per_page', 25))
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'designation' => $user->designation,
                'username' => $user->username,
                'email' => $user->email,
                'mobile' => $user->mobile === null ? null : substr($user->mobile, 2),
                'role' => $user->getRoleNames()->first(),
                'branch_ids' => $user->branches->pluck('id')->all(),
                'branches' => $user->branches->pluck('code')->join(', '),
                'is_active' => $user->is_active,
                'status' => $user->is_active ? 'active' : 'inactive',
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'locale' => $user->locale,
            ]);

        return Inertia::render('Admin/Staff/Index', [
            'staff' => $staff,
            'filters' => ['q' => $search, 'role' => $request->query('role')],
            'roles' => SaveStaffAction::assignableRoles($actor),
            'branches' => Branch::query()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(StaffRequest $request, SaveStaffAction $save): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        /** @phpstan-var array{name: string, role: string, branch_ids: list<int>} $data */
        $data = $request->safe()->except('mobile_normalized');
        $save->execute($actor, $data);

        return back()->with('success', __('staff.created'));
    }

    public function update(StaffRequest $request, User $staff, SaveStaffAction $save, TenantContext $context): RedirectResponse
    {
        $this->ensureVisible($staff, $context);
        /** @var User $actor */
        $actor = $request->user();
        /** @phpstan-var array{name: string, role: string, branch_ids: list<int>} $data */
        $data = $request->safe()->except('mobile_normalized');
        $save->execute($actor, $data, $staff);

        return back()->with('success', __('staff.updated'));
    }

    private function ensureVisible(User $staff, TenantContext $context): void
    {
        $branchIds = $context->branchIds();

        if ($branchIds !== null && $staff->branches()->withoutGlobalScopes()->whereIn('branches.id', $branchIds)->doesntExist()) {
            abort(404);
        }
    }
}
