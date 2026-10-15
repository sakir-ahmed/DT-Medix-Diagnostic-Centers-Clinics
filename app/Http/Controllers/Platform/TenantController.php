<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Actions\Platform\ChangeTenantStatusAction;
use App\Actions\Platform\UpdateTenantPlanAction;
use App\Actions\Tenants\CreateTenantAction;
use App\Enums\Module;
use App\Enums\Plan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreTenantRequest;
use App\Http\Requests\Platform\UpdateTenantPlanRequest;
use App\Jobs\ExportTenantDataJob;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $tenants = Tenant::query()
            ->withCount(['branches' => fn ($q) => $q->withoutGlobalScopes(), 'users' => fn ($q) => $q->withoutGlobalScopes()])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%")))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate((int) $request->query('per_page', 25))
            ->withQueryString();

        return Inertia::render('Platform/Tenants/Index', [
            'tenants' => $tenants->through(fn (Tenant $tenant): array => $this->present($tenant)),
            'filters' => ['q' => $search, 'status' => $request->query('status')],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Platform/Tenants/Create', [
            'plans' => $this->plans(),
            'defaultTrialEndsAt' => now()->addDays((int) config('tenancy.trial_days'))->toDateString(),
        ]);
    }

    public function store(StoreTenantRequest $request, CreateTenantAction $create): RedirectResponse
    {
        $tenant = $create->execute($request->validated());

        return redirect()->route('platform.tenants.edit', $tenant)->with('success', __('platform.tenant_created'));
    }

    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('Platform/Tenants/Edit', [
            'tenant' => $this->present($tenant) + [
                'limits' => ['branches' => $tenant->limit('branches'), 'users' => $tenant->limit('users')],
                'modules' => $tenant->modules ?? $tenant->plan->modules(),
                'subscription_ends_at' => $tenant->subscription_ends_at?->toDateString(),
            ],
            'plans' => $this->plans(),
            'modules' => Module::values(),
        ]);
    }

    public function update(UpdateTenantPlanRequest $request, Tenant $tenant, UpdateTenantPlanAction $update): RedirectResponse
    {
        $update->execute($tenant, $request->validated());

        return back()->with('success', __('platform.tenant_updated'));
    }

    public function suspend(Tenant $tenant, ChangeTenantStatusAction $action): RedirectResponse
    {
        $action->suspend($tenant);

        return back()->with('success', __('platform.tenant_suspended'));
    }

    public function resume(Tenant $tenant, ChangeTenantStatusAction $action): RedirectResponse
    {
        $action->resume($tenant);

        return back()->with('success', __('platform.tenant_resumed'));
    }

    public function export(Tenant $tenant): RedirectResponse
    {
        ExportTenantDataJob::dispatch($tenant->id);

        return back()->with('success', __('platform.export_started'));
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'domain' => $tenant->domain,
            'plan' => $tenant->plan->value,
            'status' => $tenant->status->value,
            'trial_ends_at' => $tenant->trial_ends_at?->toDateString(),
            'trial_days_left' => $tenant->trialDaysLeft(),
            'message_credits' => $tenant->message_credits,
            'branches_count' => $tenant->branches_count ?? null,
            'users_count' => $tenant->users_count ?? null,
            'created_at' => $tenant->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array{value: string, label: string, price: string}>
     */
    private function plans(): array
    {
        return array_map(fn (Plan $plan): array => [
            'value' => $plan->value,
            'label' => __('platform.plans.'.$plan->value),
            'price' => $plan->price(),
        ], Plan::cases());
    }
}
