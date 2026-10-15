<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Enums\RoleName;
use App\Exceptions\DomainException;
use App\Models\User;
use App\Services\PlanGate;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a staff account with one role and its branches.
 * Role changes are written to the activity log.
 */
final class SaveStaffAction
{
    public function __construct(private readonly PlanGate $planGate, private readonly TenantContext $context) {}

    /**
     * Roles the acting user may assign.
     *
     * @return list<string>
     */
    public static function assignableRoles(User $actor): array
    {
        if ($actor->can('branches.all') && $actor->can('settings.manage')) {
            return array_map(fn (RoleName $role): string => $role->value, RoleName::tenantRoles());
        }

        return [
            RoleName::Receptionist->value, RoleName::SampleCollector->value, RoleName::LabTechnician->value,
            RoleName::Pathologist->value, RoleName::Doctor->value,
        ];
    }

    /**
     * @param  array{name: string, designation?: string|null, username?: string|null, email?: string|null, mobile?: string|null, password?: string|null, role: string, branch_ids: list<int>, is_active?: bool, locale?: string}  $data
     */
    public function execute(User $actor, array $data, ?User $staff = null): User
    {
        if (! in_array($data['role'], self::assignableRoles($actor), true)) {
            throw DomainException::make('FORBIDDEN', 403);
        }

        $allowedBranches = $this->context->branchIds();

        if ($allowedBranches !== null && array_diff($data['branch_ids'], $allowedBranches) !== []) {
            abort(404);
        }

        if ($staff === null) {
            $tenant = $this->context->tenant();
            abort_if($tenant === null, 404);
            $this->planGate->ensureCanAddUser($tenant);
        }

        return DB::transaction(function () use ($data, $staff): User {
            $staff ??= new User;
            $previousRoles = $staff->exists ? $staff->getRoleNames()->all() : [];

            $staff->fill([
                'name' => $data['name'],
                'username' => $data['username'] ?? null,
                'email' => $data['email'] ?? null,
                'mobile' => normalize_bd_mobile($data['mobile'] ?? null),
                'is_active' => $data['is_active'] ?? true,
                'locale' => $data['locale'] ?? ($staff->locale ?? 'en'),
            ]);
            $staff->designation = $data['designation'] ?? null;

            if (! empty($data['password'])) {
                $staff->password = $data['password'];
            }

            $staff->save();
            $staff->syncRoles([$data['role']]);
            $staff->branches()->sync($data['branch_ids']);

            if ($previousRoles !== [$data['role']]) {
                activity('staff')->performedOn($staff)
                    ->withProperties(['from' => $previousRoles, 'to' => [$data['role']]])
                    ->log('staff.role_changed');
            }

            return $staff;
        });
    }
}
