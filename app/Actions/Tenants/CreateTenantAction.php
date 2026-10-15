<?php

declare(strict_types=1);

namespace App\Actions\Tenants;

use App\Enums\Plan;
use App\Enums\RoleName;
use App\Enums\TenantStatus;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Creates a tenant with its roles, a main branch and the owner account.
 */
final class CreateTenantAction
{
    public function __construct(
        private readonly SyncPermissionsAction $syncPermissions,
        private readonly TenantContext $context,
    ) {}

    /**
     * @param  array{name: string, slug: string, plan: string, name_bn?: string|null, domain?: string|null, trial_ends_at?: string|null, contact_name?: string|null, contact_mobile?: string|null, contact_email?: string|null, address?: string|null, message_credits?: int|null, owner: array{name: string, email?: string|null, mobile?: string|null, username?: string|null, password: string}}  $data
     */
    public function execute(array $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            $plan = Plan::from($data['plan']);

            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'name_bn' => $data['name_bn'] ?? null,
                'slug' => $data['slug'],
                'domain' => $data['domain'] ?? null,
                'plan' => $plan,
                'status' => TenantStatus::Trial,
                'trial_ends_at' => isset($data['trial_ends_at'])
                    ? Carbon::parse($data['trial_ends_at'])
                    : now()->addDays((int) config('tenancy.trial_days')),
                'limits' => $plan->limits(),
                'modules' => $plan->modules(),
                'settings' => [],
                'message_credits' => $data['message_credits'] ?? 100,
                'contact_name' => $data['contact_name'] ?? null,
                'contact_mobile' => normalize_bd_mobile($data['contact_mobile'] ?? null),
                'contact_email' => $data['contact_email'] ?? null,
                'address' => $data['address'] ?? null,
            ]);

            $this->syncPermissions->roles($tenant);

            return $this->context->run($tenant, function () use ($tenant, $data): Tenant {
                $branch = Branch::query()->create([
                    'code' => 'BR01',
                    'name' => __('branches.main_branch'),
                    'address' => $data['address'] ?? null,
                    'phone' => $data['contact_mobile'] ?? null,
                ]);

                $owner = User::query()->create([
                    'name' => $data['owner']['name'],
                    'email' => $data['owner']['email'] ?? null,
                    'mobile' => normalize_bd_mobile($data['owner']['mobile'] ?? null),
                    'username' => $data['owner']['username'] ?? null,
                    'password' => $data['owner']['password'],
                ]);

                $owner->assignRole(RoleName::Owner->value);
                $owner->branches()->attach($branch->id);

                activity('platform')->performedOn($tenant)->log('tenant.created');

                return $tenant;
            });
        });
    }
}
