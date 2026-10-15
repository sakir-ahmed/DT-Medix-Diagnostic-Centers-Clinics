<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Module;
use App\Exceptions\DomainException;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;

/**
 * The single place where plan limits and module toggles are enforced.
 */
final class PlanGate
{
    public function ensureModule(Tenant $tenant, Module $module): void
    {
        if (! $tenant->hasModule($module)) {
            throw DomainException::make('MODULE_DISABLED', 403, ['module' => $module->value]);
        }
    }

    public function ensureCanAddBranch(Tenant $tenant): void
    {
        $this->ensureWithinLimit($tenant, 'branches', Branch::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
    }

    public function ensureCanAddUser(Tenant $tenant): void
    {
        $this->ensureWithinLimit($tenant, 'users', User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
    }

    public function ensureMessageCredits(Tenant $tenant, int $credits = 1): void
    {
        if ($tenant->message_credits < $credits) {
            throw DomainException::make('PLAN_LIMIT_REACHED', 403, ['limit' => 'message_credits']);
        }
    }

    public function canAddBranch(Tenant $tenant): bool
    {
        return $this->withinLimit($tenant, 'branches', Branch::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
    }

    private function ensureWithinLimit(Tenant $tenant, string $key, int $current): void
    {
        if (! $this->withinLimit($tenant, $key, $current)) {
            throw DomainException::make('PLAN_LIMIT_REACHED', 403, ['limit' => $key, 'max' => $tenant->limit($key)]);
        }
    }

    private function withinLimit(Tenant $tenant, string $key, int $current): bool
    {
        $max = $tenant->limit($key);

        return $max === null || $current < $max;
    }
}
