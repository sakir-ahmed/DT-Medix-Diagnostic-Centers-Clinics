<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super-admin';
    case Owner = 'owner';
    case BranchManager = 'branch-manager';
    case Receptionist = 'receptionist';
    case SampleCollector = 'sample-collector';
    case LabTechnician = 'lab-technician';
    case Pathologist = 'pathologist';
    case Doctor = 'doctor';
    case Accountant = 'accountant';

    /**
     * @return list<self>
     */
    public static function tenantRoles(): array
    {
        return array_values(array_filter(self::cases(), fn (self $role): bool => $role !== self::SuperAdmin));
    }

    /**
     * Roles that see every branch of the tenant.
     */
    public function seesAllBranches(): bool
    {
        return in_array($this, [self::Owner, self::Accountant], true);
    }
}
