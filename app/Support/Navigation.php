<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * The admin sidebar, grouped by module. Items are shown only when the route
 * exists, the module is enabled and the user has the permission.
 */
final class Navigation
{
    /**
     * @return list<array{group: string, items: list<array{key: string, label: string, href: string, icon: string, route: string}>}>
     */
    public static function for(User $user, ?Tenant $tenant): array
    {
        $groups = [];

        foreach (self::definition() as $group => $items) {
            $visible = [];

            foreach ($items as $item) {
                if (! Route::has($item['route'])) {
                    continue;
                }

                if ($item['module'] !== null && $tenant !== null && ! $tenant->hasModule($item['module'])) {
                    continue;
                }

                if ($item['permission'] !== null && ! $user->can($item['permission'])) {
                    continue;
                }

                $visible[] = [
                    'key' => $item['key'],
                    'label' => __('nav.'.$item['key']),
                    'href' => route($item['route']),
                    'icon' => $item['icon'],
                    'route' => $item['route'],
                ];
            }

            if ($visible !== []) {
                $groups[] = ['group' => __('nav.groups.'.$group), 'items' => $visible];
            }
        }

        return $groups;
    }

    /**
     * @return array<string, list<array{key: string, route: string, icon: string, permission: string|null, module: string|null}>>
     */
    private static function definition(): array
    {
        return [
            'overview' => [
                ['key' => 'dashboard', 'route' => 'admin.dashboard', 'icon' => 'chart-line', 'permission' => 'dashboard.view', 'module' => null],
                ['key' => 'approvals', 'route' => 'admin.approvals.index', 'icon' => 'user-shield', 'permission' => 'discounts.approve', 'module' => null],
            ],
            'reception' => [
                ['key' => 'reception', 'route' => 'admin.reception.index', 'icon' => 'cash-register', 'permission' => 'orders.create', 'module' => 'orders'],
                ['key' => 'patients', 'route' => 'admin.patients.index', 'icon' => 'hospital-user', 'permission' => 'patients.view', 'module' => 'patients'],
                ['key' => 'appointments', 'route' => 'admin.appointments.index', 'icon' => 'calendar-check', 'permission' => 'appointments.view', 'module' => 'appointments'],
                ['key' => 'my_queue', 'route' => 'admin.doctor.queue', 'icon' => 'user-doctor', 'permission' => 'queue.view', 'module' => 'appointments'],
                ['key' => 'orders', 'route' => 'admin.orders.index', 'icon' => 'file-invoice', 'permission' => 'orders.view', 'module' => 'orders'],
                ['key' => 'dues', 'route' => 'admin.dues.index', 'icon' => 'money-bill-wave', 'permission' => 'dues.view', 'module' => 'dues'],
                ['key' => 'shifts', 'route' => 'admin.shifts.index', 'icon' => 'cash-register', 'permission' => 'shifts.manage', 'module' => 'billing'],
            ],
            'lab' => [
                ['key' => 'collection', 'route' => 'admin.samples.collection', 'icon' => 'vial', 'permission' => 'samples.collect', 'module' => 'lab'],
                ['key' => 'home_collections', 'route' => 'admin.home-collections.index', 'icon' => 'house-medical', 'permission' => 'home-collections.manage', 'module' => 'home_collection'],
                ['key' => 'sample_transfers', 'route' => 'admin.sample-transfers.index', 'icon' => 'truck-medical', 'permission' => 'sample-transfers.manage', 'module' => 'sample_transfer'],
                ['key' => 'receive_samples', 'route' => 'admin.samples.receive', 'icon' => 'barcode', 'permission' => 'samples.receive', 'module' => 'lab'],
                ['key' => 'worklist', 'route' => 'admin.lab.worklist', 'icon' => 'flask', 'permission' => 'lab.worklist', 'module' => 'lab'],
                ['key' => 'verification', 'route' => 'admin.reports.verification', 'icon' => 'stamp', 'permission' => 'reports.verify', 'module' => 'reports'],
                ['key' => 'reports', 'route' => 'admin.reports.index', 'icon' => 'file-medical', 'permission' => 'reports.view', 'module' => 'reports'],
                ['key' => 'critical_log', 'route' => 'admin.lab.critical', 'icon' => 'triangle-exclamation', 'permission' => 'critical.view', 'module' => 'lab'],
            ],
            'finance' => [
                ['key' => 'collections', 'route' => 'admin.collections.index', 'icon' => 'money-bill-wave', 'permission' => 'collections.view', 'module' => 'billing'],
                ['key' => 'refunds', 'route' => 'admin.refunds.index', 'icon' => 'rotate-left', 'permission' => 'refunds.approve', 'module' => 'billing'],
                ['key' => 'commissions', 'route' => 'admin.commissions.index', 'icon' => 'hand-holding-dollar', 'permission' => 'commissions.view', 'module' => 'commission'],
                ['key' => 'my_commission', 'route' => 'admin.commissions.mine', 'icon' => 'hand-holding-dollar', 'permission' => 'commissions.own', 'module' => 'commission'],
                ['key' => 'referral_performance', 'route' => 'admin.referrals.performance', 'icon' => 'chart-column', 'permission' => 'commissions.view', 'module' => 'referrals'],
                ['key' => 'expenses', 'route' => 'admin.expenses.index', 'icon' => 'receipt', 'permission' => 'expenses.view', 'module' => 'expenses'],
                ['key' => 'mis_reports', 'route' => 'admin.mis.index', 'icon' => 'chart-pie', 'permission' => 'mis.view', 'module' => 'dashboard'],
            ],
            'catalog' => [
                ['key' => 'tests', 'route' => 'admin.tests.index', 'icon' => 'flask', 'permission' => 'catalog.view', 'module' => null],
                ['key' => 'packages', 'route' => 'admin.packages.index', 'icon' => 'boxes-stacked', 'permission' => 'catalog.view', 'module' => null],
                ['key' => 'report_templates', 'route' => 'admin.report-templates.index', 'icon' => 'file-lines', 'permission' => 'templates.manage', 'module' => null],
                ['key' => 'message_templates', 'route' => 'admin.message-templates.index', 'icon' => 'comment-sms', 'permission' => 'message-templates.manage', 'module' => null],
                ['key' => 'doctors', 'route' => 'admin.doctors.index', 'icon' => 'user-doctor', 'permission' => 'doctors.view', 'module' => null],
                ['key' => 'referrers', 'route' => 'admin.referrers.index', 'icon' => 'handshake', 'permission' => 'referrers.view', 'module' => null],
            ],
            'settings' => [
                ['key' => 'branches', 'route' => 'admin.branches.index', 'icon' => 'building', 'permission' => 'branches.manage', 'module' => null],
                ['key' => 'staff', 'route' => 'admin.staff.index', 'icon' => 'users', 'permission' => 'staff.manage', 'module' => null],
                ['key' => 'settings', 'route' => 'admin.settings.edit', 'icon' => 'gear', 'permission' => 'settings.manage', 'module' => null],
                ['key' => 'import', 'route' => 'admin.imports.index', 'icon' => 'file-import', 'permission' => 'import.manage', 'module' => 'import'],
                ['key' => 'access_log', 'route' => 'admin.access-logs.index', 'icon' => 'eye', 'permission' => 'access-log.view', 'module' => null],
                ['key' => 'activity_log', 'route' => 'admin.activity-logs.index', 'icon' => 'clock-rotate-left', 'permission' => 'activity-log.view', 'module' => null],
            ],
        ];
    }
}
