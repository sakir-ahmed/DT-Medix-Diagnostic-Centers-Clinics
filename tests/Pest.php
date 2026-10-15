<?php

declare(strict_types=1);

use App\Actions\Tenants\SyncPermissionsAction;
use App\Enums\Plan;
use App\Enums\RoleName;
use App\Enums\TenantStatus;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
pest()->extend(TestCase::class)->in('Unit');

/**
 * A tenant with its roles and a main branch (BR01).
 *
 * @param  array<string, mixed>  $attributes
 */
function makeTenant(array $attributes = []): Tenant
{
    $tenant = Tenant::factory()->create($attributes + ['status' => TenantStatus::Active, 'plan' => Plan::Pro]);
    app(SyncPermissionsAction::class)->roles($tenant);

    app(TenantContext::class)->run($tenant, fn () => Branch::query()->create(['code' => 'BR01', 'name' => 'Main']));

    return $tenant;
}

function mainBranch(Tenant $tenant): Branch
{
    return Branch::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('code', 'BR01')->firstOrFail();
}

function makeBranch(Tenant $tenant, string $code = 'BR02'): Branch
{
    return app(TenantContext::class)->run($tenant, fn () => Branch::query()->create(['code' => $code, 'name' => 'Branch '.$code]));
}

/**
 * A user of the tenant with the given role, attached to a branch (main by default).
 *
 * @param  array<string, mixed>  $attributes
 */
function makeUser(Tenant $tenant, RoleName|string $role = RoleName::Owner, ?Branch $branch = null, array $attributes = []): User
{
    $roleName = $role instanceof RoleName ? $role->value : $role;

    return app(TenantContext::class)->run($tenant, function () use ($tenant, $roleName, $branch, $attributes): User {
        /** @var User $user */
        $user = User::factory()->create($attributes);
        $user->forceFill(['tenant_id' => $tenant->id])->save();
        $user->assignRole($roleName);
        $user->branches()->attach(($branch ?? mainBranch($tenant))->id);

        return $user->fresh();
    });
}

/**
 * Run a callback inside a tenant context (for direct model assertions).
 *
 * @template T
 *
 * @param  callable(): T  $callback
 * @return T
 */
function inTenant(Tenant $tenant, callable $callback): mixed
{
    return app(TenantContext::class)->run($tenant, $callback);
}
