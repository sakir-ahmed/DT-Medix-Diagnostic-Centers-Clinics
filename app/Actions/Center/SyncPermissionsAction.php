<?php

declare(strict_types=1);

namespace App\Actions\Center;

use App\Enums\RoleName;
use App\Support\Permissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the permission records and the one role set of this installation.
 * Safe to run again: missing permissions are added to existing roles.
 */
final class SyncPermissionsAction
{
    public function __construct(private readonly PermissionRegistrar $registrar) {}

    public function execute(): void
    {
        foreach (Permissions::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate(RoleName::SuperAdmin->value, 'web');

        foreach (RoleName::staffRoles() as $roleName) {
            /** @var Role $role */
            $role = Role::findOrCreate($roleName->value, 'web');
            $role->syncPermissions(Permissions::forRole($roleName));
        }

        $this->registrar->forgetCachedPermissions();
    }
}
