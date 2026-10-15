<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\Activity;
use App\Models\User;

beforeEach(function (): void {
    $this->tenant = makeTenant(['limits' => ['branches' => 5, 'users' => 4]]);
    $this->owner = makeUser($this->tenant, RoleName::Owner);
});

function staffPayload(array $overrides = []): array
{
    return $overrides + [
        'name' => 'Rina Akter', 'username' => 'rina', 'password' => 'password123', 'role' => 'receptionist',
        'branch_ids' => [], 'is_active' => true,
    ];
}

it('creates staff with a role and branches', function (): void {
    $this->actingAs($this->owner)->post(route('admin.staff.store'), staffPayload(['branch_ids' => [mainBranch($this->tenant)->id], 'mobile' => '01712345678']))->assertSessionHasNoErrors();

    $user = User::query()->withoutGlobalScopes()->where('username', 'rina')->firstOrFail();

    expect($user->tenant_id)->toBe($this->tenant->id)
        ->and($user->mobile)->toBe('8801712345678')
        ->and(inTenant($this->tenant, fn () => $user->hasRole('receptionist')))->toBeTrue()
        ->and($user->branches()->withoutGlobalScopes()->count())->toBe(1);
});

it('enforces the plan user limit', function (): void {
    makeUser($this->tenant, RoleName::Receptionist);
    makeUser($this->tenant, RoleName::Receptionist);
    makeUser($this->tenant, RoleName::Receptionist);

    $this->actingAs($this->owner)->post(route('admin.staff.store'), staffPayload(['branch_ids' => [mainBranch($this->tenant)->id]]))
        ->assertSessionHas('error_code', 'PLAN_LIMIT_REACHED');
});

it('logs role changes', function (): void {
    $staff = makeUser($this->tenant, RoleName::Receptionist);

    $this->actingAs($this->owner)->put(route('admin.staff.update', $staff), staffPayload(['username' => $staff->username, 'role' => 'branch-manager', 'branch_ids' => [mainBranch($this->tenant)->id], 'password' => null]))
        ->assertSessionHasNoErrors();

    expect(inTenant($this->tenant, fn () => $staff->fresh()->hasRole('branch-manager')))->toBeTrue()
        ->and(inTenant($this->tenant, fn () => Activity::query()->where('description', 'staff.role_changed')->count()))->toBe(1);
});

it('does not let a branch manager assign the owner role or other branches', function (): void {
    $manager = makeUser($this->tenant, RoleName::BranchManager);
    $other = makeBranch($this->tenant, 'BR02');

    $this->actingAs($manager)->post(route('admin.staff.store'), staffPayload(['role' => 'owner', 'branch_ids' => [mainBranch($this->tenant)->id]]))
        ->assertSessionHas('error_code', 'FORBIDDEN');
    $this->actingAs($manager)->post(route('admin.staff.store'), staffPayload(['branch_ids' => [$other->id]]))->assertNotFound();
});

it('hides staff of other branches from a branch manager', function (): void {
    $manager = makeUser($this->tenant, RoleName::BranchManager);
    $elsewhere = makeUser($this->tenant, RoleName::Receptionist, makeBranch($this->tenant, 'BR02'));

    $this->actingAs($manager)->put(route('admin.staff.update', $elsewhere), staffPayload(['username' => $elsewhere->username, 'branch_ids' => [mainBranch($this->tenant)->id]]))->assertNotFound();
});

it('returns 404 for staff of another tenant', function (): void {
    $foreign = makeUser(makeTenant(), RoleName::Receptionist);

    $this->actingAs($this->owner)->put(route('admin.staff.update', $foreign), staffPayload(['branch_ids' => [mainBranch($this->tenant)->id]]))->assertNotFound();
});

it('rejects a duplicate mobile with a field error', function (): void {
    makeUser($this->tenant, RoleName::Receptionist, attributes: ['mobile' => '8801712345678']);

    $this->actingAs($this->owner)->post(route('admin.staff.store'), staffPayload(['mobile' => '01712345678', 'branch_ids' => [mainBranch($this->tenant)->id]]))
        ->assertSessionHasErrors('mobile_normalized');
});
