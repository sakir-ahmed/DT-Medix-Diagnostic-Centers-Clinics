<?php

declare(strict_types=1);

namespace App\Actions\Tenants;

use App\Enums\RoleName;
use App\Models\Tenant;
use App\Support\Permissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the global permission records and the default roles of a tenant.
 * Safe to run again: missing permissions are added to existing roles.
 */
final class SyncPermissionsAction
{
    public function __construct(private readonly PermissionRegistrar $registrar) {}

    public function permissions(): void
    {
        foreach (Permissions::all() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $previous = $this->registrar->getPermissionsTeamId();
        $this->registrar->setPermissionsTeamId(null);
        Role::findOrCreate(RoleName::SuperAdmin->value, 'web');
        $this->registrar->setPermissionsTeamId($previous);

        $this->registrar->forgetCachedPermissions();
    }

    public function roles(Tenant $tenant): void
    {
        $this->permissions();

        $previous = $this->registrar->getPermissionsTeamId();
        $this->registrar->setPermissionsTeamId($tenant->id);

        foreach (RoleName::tenantRoles() as $roleName) {
            /** @var Role $role */
            $role = Role::query()->firstOrCreate([
                'name' => $roleName->value,
                'guard_name' => 'web',
                'tenant_id' => $tenant->id,
            ]);

            $role->syncPermissions(Permissions::forRole($roleName));
        }

        $this->registrar->setPermissionsTeamId($previous);
        $this->registrar->forgetCachedPermissions();
    }
}
