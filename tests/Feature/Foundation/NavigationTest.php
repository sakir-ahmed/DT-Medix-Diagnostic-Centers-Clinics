<?php

declare(strict_types=1);

use App\Enums\RoleName;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return list<string>
 */
function navigationKeys(Assert $page): array
{
    $keys = [];
    foreach ($page->toArray()['props']['navigation'] as $group) {
        foreach ($group['items'] as $item) {
            $keys[] = $item['key'];
        }
    }

    return $keys;
}

it('shows each role only its menu', function (RoleName $role, array $mustHave, array $mustNotHave): void {
    $tenant = makeTenant();
    $user = makeUser($tenant, $role);

    $this->actingAs($user)->get(route('admin.dashboard'))->assertInertia(function (Assert $page) use ($mustHave, $mustNotHave): void {
        $keys = navigationKeys($page);

        foreach ($mustHave as $key) {
            expect($keys)->toContain($key);
        }
        foreach ($mustNotHave as $key) {
            expect($keys)->not->toContain($key);
        }
    });
})->with([
    'owner' => [RoleName::Owner, ['dashboard'], []],
    'receptionist' => [RoleName::Receptionist, ['dashboard'], ['access_log', 'activity_log', 'settings', 'staff', 'commissions']],
    'lab-technician' => [RoleName::LabTechnician, ['dashboard'], ['settings', 'staff', 'commissions', 'collections']],
    'doctor' => [RoleName::Doctor, ['dashboard'], ['settings', 'commissions', 'collections']],
    'accountant' => [RoleName::Accountant, ['dashboard'], ['settings', 'staff', 'access_log']],
]);

it('shares permissions and tenant details with the frontend', function (): void {
    $tenant = makeTenant();

    $this->actingAs(makeUser($tenant, RoleName::Receptionist))
        ->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('tenant.id', $tenant->id)
            ->where('auth.roles', ['receptionist'])
            ->has('translations.common')
            ->where('current_branch_id', mainBranch($tenant)->id));
});
