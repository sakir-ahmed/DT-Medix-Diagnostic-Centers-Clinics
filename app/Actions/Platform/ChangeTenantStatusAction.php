<?php

declare(strict_types=1);

namespace App\Actions\Platform;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;

final class ChangeTenantStatusAction
{
    public function suspend(Tenant $tenant): Tenant
    {
        $tenant->forceFill([
            'status' => TenantStatus::Suspended,
            'suspended_at' => now(),
            'scheduled_deletion_at' => now()->addDays((int) config('tenancy.retention_days')),
        ])->save();

        app(TenantContext::class)->run($tenant, fn () => activity('platform')->performedOn($tenant)->log('tenant.suspended'));

        return $tenant;
    }

    public function resume(Tenant $tenant): Tenant
    {
        $status = $tenant->trial_ends_at !== null && $tenant->trial_ends_at->isFuture() && $tenant->subscription_ends_at === null
            ? TenantStatus::Trial
            : TenantStatus::Active;

        $tenant->forceFill([
            'status' => $status,
            'suspended_at' => null,
            'read_only_since' => null,
            'scheduled_deletion_at' => null,
        ])->save();

        app(TenantContext::class)->run($tenant, fn () => activity('platform')->performedOn($tenant)->log('tenant.resumed'));

        return $tenant;
    }
}
