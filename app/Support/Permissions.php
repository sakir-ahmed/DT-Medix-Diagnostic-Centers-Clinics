<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\RoleName;

/**
 * Permission catalogue and the default permissions of each tenant role.
 * Policies and menus check permissions, never role names.
 */
final class Permissions
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            'dashboard.view', 'branches.manage', 'branches.all', 'settings.manage', 'staff.manage',
            'access-log.view', 'activity-log.view',
            'patients.view', 'patients.create', 'patients.update', 'patients.export',
            'doctors.view', 'doctors.manage', 'schedules.manage',
            'appointments.view', 'appointments.manage', 'queue.view', 'consultations.manage',
            'catalog.view', 'catalog.manage', 'templates.manage', 'message-templates.manage',
            'referrers.view', 'referrers.manage',
            'orders.view', 'orders.create', 'orders.cancel', 'invoices.discount', 'discounts.approve',
            'payments.collect', 'refunds.request', 'refunds.approve', 'cancellations.approve',
            'samples.collect', 'samples.receive', 'samples.reject',
            'home-collections.manage', 'sample-transfers.manage',
            'lab.worklist', 'results.enter', 'critical.view',
            'reports.view', 'reports.verify', 'reports.release', 'reports.release-override', 'reports.amend',
            'dues.view', 'dues.collect', 'dues.remind',
            'shifts.manage', 'shifts.review', 'collections.view',
            'commissions.view', 'commissions.manage', 'commissions.own', 'payouts.manage',
            'expenses.view', 'expenses.manage', 'mis.view',
            'import.manage', 'export.data',
        ];
    }

    /**
     * @return list<string>
     */
    public static function forRole(RoleName $role): array
    {
        return match ($role) {
            RoleName::SuperAdmin => [],
            RoleName::Owner => array_values(array_diff(self::all(), ['commissions.own'])),
            RoleName::BranchManager => [
                'dashboard.view', 'staff.manage',
                'patients.view', 'patients.create', 'patients.update',
                'doctors.view', 'doctors.manage', 'schedules.manage',
                'appointments.view', 'appointments.manage',
                'catalog.view', 'catalog.manage', 'referrers.view', 'referrers.manage',
                'orders.view', 'orders.create', 'orders.cancel', 'invoices.discount', 'discounts.approve',
                'payments.collect', 'refunds.request', 'refunds.approve', 'cancellations.approve',
                'samples.collect', 'samples.receive', 'samples.reject',
                'home-collections.manage', 'sample-transfers.manage', 'lab.worklist', 'critical.view',
                'reports.view', 'reports.release', 'reports.release-override',
                'dues.view', 'dues.collect', 'dues.remind',
                'shifts.manage', 'shifts.review', 'collections.view',
                'expenses.view', 'expenses.manage', 'mis.view',
            ],
            RoleName::Receptionist => [
                'dashboard.view', 'patients.view', 'patients.create', 'patients.update',
                'doctors.view', 'appointments.view', 'appointments.manage', 'catalog.view', 'referrers.view',
                'orders.view', 'orders.create', 'orders.cancel', 'invoices.discount',
                'payments.collect', 'refunds.request', 'reports.view', 'reports.release',
                'dues.view', 'dues.collect', 'dues.remind', 'shifts.manage',
            ],
            RoleName::SampleCollector => [
                'dashboard.view', 'patients.view', 'orders.view', 'samples.collect',
                'home-collections.manage', 'sample-transfers.manage',
            ],
            RoleName::LabTechnician => [
                'dashboard.view', 'patients.view', 'catalog.view', 'samples.receive', 'samples.reject',
                'sample-transfers.manage', 'lab.worklist', 'results.enter', 'critical.view', 'reports.view',
            ],
            RoleName::Pathologist => [
                'dashboard.view', 'patients.view', 'catalog.view', 'lab.worklist', 'results.enter',
                'critical.view', 'reports.view', 'reports.verify', 'reports.amend', 'templates.manage',
            ],
            RoleName::Doctor => [
                'dashboard.view', 'patients.view', 'appointments.view', 'queue.view',
                'consultations.manage', 'reports.view', 'commissions.own',
            ],
            RoleName::Accountant => [
                'dashboard.view', 'branches.all', 'referrers.view', 'dues.view', 'dues.collect',
                'shifts.review', 'collections.view', 'commissions.view', 'commissions.manage',
                'payouts.manage', 'expenses.view', 'expenses.manage', 'mis.view',
            ],
        };
    }
}
