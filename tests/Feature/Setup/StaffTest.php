<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    makeCenter();
    $this->owner = makeUser(RoleName::Owner);
});

function staffPayload(array $overrides = []): array
{
    return $overrides + [
        'name' => 'Rina Akter', 'username' => 'rina', 'password' => 'password123', 'role' => 'receptionist',
        'branch_ids' => [], 'is_active' => true,
    ];
}

it('creates staff with a role and branches', function (): void {
    $this->actingAs($this->owner)->post(route('admin.staff.store'), staffPayload(['branch_ids' => [mainBranch()->id], 'mobile' => '01712345678']))->assertSessionHasNoErrors();

    $user = User::query()->withoutGlobalScopes()->where('username', 'rina')->firstOrFail();

    expect($user->mobile)->toBe('8801712345678')
        ->and(value(fn () => $user->hasRole('receptionist')))->toBeTrue()
        ->and($user->branches()->withoutGlobalScopes()->count())->toBe(1);
});

it('logs role changes', function (): void {
    $staff = makeUser(RoleName::Receptionist);

    $this->actingAs($this->owner)->put(route('admin.staff.update', $staff), staffPayload(['username' => $staff->username, 'role' => 'branch-manager', 'branch_ids' => [mainBranch()->id], 'password' => null]))
        ->assertSessionHasNoErrors();

    expect(value(fn () => $staff->fresh()->hasRole('branch-manager')))->toBeTrue()
        ->and(value(fn () => Activity::query()->where('description', 'staff.role_changed')->count()))->toBe(1);
});

it('does not let a branch manager assign the owner role or other branches', function (): void {
    $manager = makeUser(RoleName::BranchManager);
    $other = makeBranch('BR02');

    $this->actingAs($manager)->post(route('admin.staff.store'), staffPayload(['role' => 'owner', 'branch_ids' => [mainBranch()->id]]))
        ->assertSessionHas('error_code', 'FORBIDDEN');
    $this->actingAs($manager)->post(route('admin.staff.store'), staffPayload(['branch_ids' => [$other->id]]))->assertNotFound();
});

it('hides staff of other branches from a branch manager', function (): void {
    $manager = makeUser(RoleName::BranchManager);
    $elsewhere = makeUser(RoleName::Receptionist, makeBranch('BR02'));

    $this->actingAs($manager)->put(route('admin.staff.update', $elsewhere), staffPayload(['username' => $elsewhere->username, 'branch_ids' => [mainBranch()->id]]))->assertNotFound();
});

it('rejects a duplicate mobile with a field error', function (): void {
    makeUser(RoleName::Receptionist, attributes: ['mobile' => '8801712345678']);

    $this->actingAs($this->owner)->post(route('admin.staff.store'), staffPayload(['mobile' => '01712345678', 'branch_ids' => [mainBranch()->id]]))
        ->assertSessionHasErrors('mobile_normalized');
});
