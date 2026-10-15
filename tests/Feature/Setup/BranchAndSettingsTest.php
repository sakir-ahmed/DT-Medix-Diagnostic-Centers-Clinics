<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\Activity;
use App\Models\Branch;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->tenant = makeTenant(['limits' => ['branches' => 2, 'users' => 20]]);
    $this->owner = makeUser($this->tenant, RoleName::Owner);
});

it('lets the owner create and update branches', function (): void {
    $this->actingAs($this->owner)->post(route('admin.branches.store'), ['code' => 'br02', 'name' => 'Mirpur', 'phone' => '01711000000'])->assertSessionHasNoErrors();

    $branch = inTenant($this->tenant, fn () => Branch::query()->where('code', 'BR02')->firstOrFail());

    $this->actingAs($this->owner)->put(route('admin.branches.update', $branch), ['code' => 'BR02', 'name' => 'Mirpur 10'])->assertSessionHasNoErrors();
    expect($branch->fresh()->name)->toBe('Mirpur 10');
});

it('rejects a duplicate branch code within the tenant only', function (): void {
    $this->actingAs($this->owner)->post(route('admin.branches.store'), ['code' => 'BR01', 'name' => 'X'])->assertSessionHasErrors('code');

    $other = makeTenant();
    expect(mainBranch($other)->code)->toBe('BR01');
});

it('enforces the plan branch limit', function (): void {
    makeBranch($this->tenant, 'BR02');

    $this->actingAs($this->owner)->post(route('admin.branches.store'), ['code' => 'BR03', 'name' => 'Third'])
        ->assertSessionHas('error_code', 'PLAN_LIMIT_REACHED');
});

it('deactivates a branch with an activity log entry', function (): void {
    $branch = makeBranch($this->tenant, 'BR02');

    $this->actingAs($this->owner)->post(route('admin.branches.toggle', $branch), ['active' => false])->assertRedirect();

    expect($branch->fresh()->is_active)->toBeFalse()
        ->and(inTenant($this->tenant, fn () => Activity::query()->where('description', 'branch.deactivated')->count()))->toBe(1);
});

it('forbids branch management for other roles and other tenants', function (): void {
    $manager = makeUser($this->tenant, RoleName::BranchManager);
    $foreign = mainBranch(makeTenant());

    $this->actingAs($manager)->get(route('admin.branches.index'))->assertForbidden();
    $this->actingAs($this->owner)->put(route('admin.branches.update', $foreign), ['code' => 'BR01', 'name' => 'Hack'])->assertNotFound();
});

it('saves tenant settings and logs the changed keys', function (): void {
    $this->actingAs($this->owner)->get(route('admin.settings.edit'))->assertInertia(fn (Assert $page) => $page->where('settings.release_rule', 'hold_until_paid'));

    $settings = $this->tenant->allSettings();
    $settings['discount_limit_percent'] = 15;
    $settings['release_rule'] = 'manager_override';

    $this->actingAs($this->owner)->put(route('admin.settings.update'), ['name' => 'New Name', 'settings' => $settings])->assertSessionHasNoErrors();

    $tenant = $this->tenant->fresh();
    expect($tenant->name)->toBe('New Name')
        ->and($tenant->setting('discount_limit_percent'))->toBe(15)
        ->and($tenant->setting('release_rule'))->toBe('manager_override')
        ->and(inTenant($this->tenant, fn () => Activity::query()->where('description', 'settings.updated')->first()?->properties['changed']))->toContain('release_rule');
});

it('forbids settings for non-owners', function (): void {
    $this->actingAs(makeUser($this->tenant, RoleName::Receptionist))->get(route('admin.settings.edit'))->assertForbidden();
});
