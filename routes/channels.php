<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
| Private channels are authorized by tenant, branch and permission.
| Payloads carry IDs only, never patient data.
*/

$branchAccess = function (User $user, int $tenantId, int $branchId, string $permission): bool {
    if ($user->tenant_id !== $tenantId || ! $user->can($permission)) {
        return false;
    }

    $branchIds = $user->accessibleBranchIds();

    return Branch::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->whereKey($branchId)->exists()
        && ($branchIds === null || in_array($branchId, $branchIds, true));
};

Broadcast::channel('tenant.{tenantId}.branch.{branchId}.lab', fn (User $user, int $tenantId, int $branchId): bool => $branchAccess($user, $tenantId, $branchId, 'lab.worklist'));

Broadcast::channel('tenant.{tenantId}.branch.{branchId}.reception', fn (User $user, int $tenantId, int $branchId): bool => $branchAccess($user, $tenantId, $branchId, 'orders.view'));

Broadcast::channel('tenant.{tenantId}.branch.{branchId}.alerts', fn (User $user, int $tenantId, int $branchId): bool => $branchAccess($user, $tenantId, $branchId, 'dashboard.view'));

Broadcast::channel('tenant.{tenantId}.doctor.{doctorId}.queue', fn (User $user, int $tenantId, int $doctorId): bool => $user->tenant_id === $tenantId && $user->can('appointments.view'));

Broadcast::channel('tenant.{tenantId}.owner.alerts', fn (User $user, int $tenantId): bool => $user->tenant_id === $tenantId && $user->can('branches.all'));
