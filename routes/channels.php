<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
| Private channels are authorized by branch and permission (docs/api.md
| section 7). Payloads carry IDs only, never patient data.
*/

$branchAccess = function (User $user, int $branchId, string $permission): bool {
    if (! $user->can($permission)) {
        return false;
    }

    $branchIds = $user->accessibleBranchIds();

    return Branch::query()->withoutGlobalScopes()->whereKey($branchId)->exists()
        && ($branchIds === null || in_array($branchId, $branchIds, true));
};

Broadcast::channel('branch.{branchId}.lab', fn (User $user, int $branchId): bool => $branchAccess($user, $branchId, 'lab.worklist'));

Broadcast::channel('branch.{branchId}.reception', fn (User $user, int $branchId): bool => $branchAccess($user, $branchId, 'orders.view'));

Broadcast::channel('branch.{branchId}.alerts', fn (User $user, int $branchId): bool => $branchAccess($user, $branchId, 'dashboard.view'));

Broadcast::channel('doctor.{doctorId}.queue', fn (User $user, int $doctorId): bool => $user->can('appointments.view'));

Broadcast::channel('owner.alerts', fn (User $user): bool => $user->can('branches.all'));
