<?php

declare(strict_types=1);

use App\Actions\Tenants\SyncPermissionsAction;
use App\Enums\RoleName;
use App\Enums\TenantStatus;
use App\Models\Activity;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function superAdmin(): User
{
    app(SyncPermissionsAction::class)->permissions();
    $admin = User::factory()->create(['tenant_id' => null]);
    app(PermissionRegistrar::class)->setPermissionsTeamId(User::PLATFORM_TEAM_ID);
    $admin->assignRole(RoleName::SuperAdmin->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    return $admin->fresh();
}

it('lets the super-admin create a tenant with roles, main branch and owner', function (): void {
    $admin = superAdmin();

    $this->actingAs($admin)->post(route('platform.tenants.store'), [
        'name' => 'Green Lab',
        'slug' => 'greenlab',
        'plan' => 'growth',
        'owner' => ['name' => 'Owner', 'email' => 'owner@greenlab.test', 'password' => 'password123'],
    ])->assertRedirect();

    $tenant = Tenant::query()->where('slug', 'greenlab')->firstOrFail();
    $owner = User::query()->withoutGlobalScopes()->where('email', 'owner@greenlab.test')->firstOrFail();

    expect($tenant->status)->toBe(TenantStatus::Trial)
        ->and($tenant->trial_ends_at->isSameDay(now()->addDays(30)))->toBeTrue()
        ->and($tenant->limit('branches'))->toBe(3)
        ->and(Branch::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->count())->toBe(1)
        ->and($owner->tenant_id)->toBe($tenant->id)
        ->and(inTenant($tenant, fn () => $owner->fresh()->hasRole('owner')))->toBeTrue()
        ->and(inTenant($tenant, fn () => Role::query()->where('tenant_id', $tenant->id)->count()))->toBe(8);
});

it('shows field errors when creating an invalid tenant', function (): void {
    $this->actingAs(superAdmin())
        ->post(route('platform.tenants.store'), ['name' => '', 'slug' => 'Bad Slug', 'plan' => 'x'])
        ->assertSessionHasErrors(['name', 'slug', 'plan', 'owner.name', 'owner.password']);
});

it('forbids tenant users from the platform area', function (): void {
    $tenant = makeTenant();

    $this->actingAs(makeUser($tenant, RoleName::Owner))->get(route('platform.tenants.index'))->assertForbidden();
});

it('redirects the super-admin away from the tenant admin', function (): void {
    $this->actingAs(superAdmin())->get(route('admin.dashboard'))->assertRedirect(route('platform.tenants.index'));
});

it('lists tenants for the super-admin', function (): void {
    makeTenant(['name' => 'Alpha Lab']);

    $this->actingAs(superAdmin())
        ->get(route('platform.tenants.index'))
        ->assertInertia(fn (Assert $page) => $page->component('Platform/Tenants/Index')->has('tenants.data', 1));
});

it('updates plan, limits and modules', function (): void {
    $tenant = makeTenant();

    $this->actingAs(superAdmin())->put(route('platform.tenants.update', $tenant), [
        'plan' => 'starter', 'limits' => ['branches' => 2, 'users' => 4], 'modules' => ['patients', 'orders'], 'message_credits' => 50,
    ])->assertRedirect();

    $tenant->refresh();
    expect($tenant->limit('branches'))->toBe(2)
        ->and($tenant->hasModule('patients'))->toBeTrue()
        ->and($tenant->hasModule('commission'))->toBeFalse()
        ->and($tenant->message_credits)->toBe(50);
});

it('blocks staff of a suspended tenant and lets them back after resume', function (): void {
    $tenant = makeTenant();
    $owner = makeUser($tenant);
    $admin = superAdmin();

    $this->actingAs($admin)->post(route('platform.tenants.suspend', $tenant))->assertRedirect();
    expect($tenant->fresh()->status)->toBe(TenantStatus::Suspended);

    $this->actingAs($owner)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($owner)->getJson('/api/v1/auth/me')->assertOk();

    $token = $owner->createToken('t', ['staff'])->plainTextToken;
    Route::middleware(['auth:sanctum', 'tenant', 'tenant.active'])->get('/api/v1/_probe', fn () => 'ok');
    $this->withToken($token)->getJson('/api/v1/_probe')->assertForbidden()->assertJsonPath('error.code', 'TENANT_SUSPENDED');
    $this->withoutToken();

    $this->actingAs($admin)->post(route('platform.tenants.resume', $tenant))->assertRedirect();
    $this->actingAs($owner)->get(route('admin.dashboard'))->assertOk();
});

it('lets a read-only tenant read but not write', function (): void {
    $tenant = makeTenant(['status' => TenantStatus::ReadOnly]);
    $owner = makeUser($tenant);

    $this->actingAs($owner)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($owner)->put(route('admin.profile.pin'), ['current_password' => 'password', 'pin' => '1234', 'pin_confirmation' => '1234'])
        ->assertRedirect()
        ->assertSessionHas('error_code', 'TENANT_READ_ONLY');
});

it('impersonates the owner with a banner and audit entries', function (): void {
    $tenant = makeTenant();
    $owner = makeUser($tenant);
    $admin = superAdmin();

    $this->actingAs($admin)->post(route('platform.tenants.impersonate', $tenant))->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($owner);

    $this->get(route('admin.dashboard'))->assertInertia(fn (Assert $page) => $page->where('impersonating', true));

    expect(inTenant($tenant, fn () => Activity::query()->where('description', 'impersonation.started')->count()))->toBe(1);

    $this->post(route('impersonation.leave'))->assertRedirect(route('platform.tenants.index'));
    $this->assertAuthenticatedAs($admin);
    expect(Activity::query()->withoutGlobalScopes()->where('description', 'impersonation.ended')->count())->toBe(1);
});

it('exports all tenant data to a zip on the private disk', function (): void {
    $tenant = makeTenant(['slug' => 'exp']);
    makeUser($tenant);
    $other = makeTenant(['slug' => 'other']);
    makeUser($other, attributes: ['name' => 'Foreign Person']);

    $this->artisan('tenant:export', ['tenant' => 'exp'])->assertSuccessful();

    $files = Storage::disk('local')->allFiles("tenants/{$tenant->id}/exports");
    expect($files)->toHaveCount(1);

    $zip = new ZipArchive;
    $zip->open(Storage::disk('local')->path($files[0]));
    $users = json_decode((string) $zip->getFromName('data/users.json'), true);

    expect($users)->toHaveCount(1)
        ->and(collect($users)->pluck('name')->all())->not->toContain('Foreign Person')
        ->and(array_key_exists('password', $users[0]))->toBeFalse();
});
