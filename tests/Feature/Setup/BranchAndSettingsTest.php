<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\Branch;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    makeCenter();
    $this->owner = makeUser(RoleName::Owner);
});

it('lets the owner create and update branches', function (): void {
    $this->actingAs($this->owner)->post(route('admin.branches.store'), ['code' => 'br02', 'name' => 'Mirpur', 'phone' => '01711000000'])->assertSessionHasNoErrors();

    $branch = value(fn () => Branch::query()->where('code', 'BR02')->firstOrFail());

    $this->actingAs($this->owner)->put(route('admin.branches.update', $branch), ['code' => 'BR02', 'name' => 'Mirpur 10'])->assertSessionHasNoErrors();
    expect($branch->fresh()->name)->toBe('Mirpur 10');
});

it('rejects a duplicate branch code', function (): void {
    $this->actingAs($this->owner)->post(route('admin.branches.store'), ['code' => 'BR01', 'name' => 'X'])->assertSessionHasErrors('code');
});

it('deactivates a branch with an activity log entry', function (): void {
    $branch = makeBranch('BR02');

    $this->actingAs($this->owner)->post(route('admin.branches.toggle', $branch), ['active' => false])->assertRedirect();

    expect($branch->fresh()->is_active)->toBeFalse()
        ->and(value(fn () => Activity::query()->where('description', 'branch.deactivated')->count()))->toBe(1);
});

it('forbids branch management for other roles', function (): void {
    $manager = makeUser(RoleName::BranchManager);

    $this->actingAs($manager)->get(route('admin.branches.index'))->assertForbidden();
});

it('saves center settings and logs the changed keys', function (): void {
    $this->actingAs($this->owner)->get(route('admin.settings.edit'))->assertInertia(fn (Assert $page) => $page->where('settings.release_rule', 'hold_until_paid'));

    $settings = settings()->rules();
    $settings['discount_limit_percent'] = 15;
    $settings['release_rule'] = 'manager_override';

    $this->actingAs($this->owner)->put(route('admin.settings.update'), ['name' => 'New Name', 'settings' => $settings])->assertSessionHasNoErrors();

    expect(setting('center_name'))->toBe('New Name')
        ->and(setting('discount_limit_percent'))->toBe(15)
        ->and(setting('release_rule'))->toBe('manager_override')
        ->and(Activity::query()->where('description', 'settings.updated')->first()?->properties['changed'])->toContain('release_rule');
});

it('forbids settings for non-owners', function (): void {
    $this->actingAs(makeUser(RoleName::Receptionist))->get(route('admin.settings.edit'))->assertForbidden();
});
