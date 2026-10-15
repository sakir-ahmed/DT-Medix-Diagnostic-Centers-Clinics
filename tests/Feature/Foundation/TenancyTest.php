<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Http\Middleware\ResolveTenant;
use App\Jobs\SendSmsJob;
use App\Models\Activity;
use App\Models\Branch;
use App\Models\PatientAccessLog;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\Fixtures\CaptureTenantJob;

beforeEach(function (): void {
    $this->tenantA = makeTenant(['slug' => 'alpha']);
    $this->tenantB = makeTenant(['slug' => 'beta']);
    $this->ownerA = makeUser($this->tenantA, RoleName::Owner);
    $this->ownerB = makeUser($this->tenantB, RoleName::Owner);
});

it('scopes queries to the current tenant', function (): void {
    $branchesA = inTenant($this->tenantA, fn () => Branch::query()->pluck('tenant_id')->unique()->all());
    $usersB = inTenant($this->tenantB, fn () => User::query()->pluck('tenant_id')->unique()->all());

    expect($branchesA)->toBe([$this->tenantA->id])
        ->and($usersB)->toBe([$this->tenantB->id]);
});

it('fills tenant_id from the context and never accepts it from input', function (): void {
    $branch = inTenant($this->tenantA, fn () => Branch::query()->create(['code' => 'BR09', 'name' => 'X']));

    expect($branch->tenant_id)->toBe($this->tenantA->id)
        ->and(fn () => inTenant($this->tenantA, fn () => Branch::query()->create(['code' => 'BR08', 'name' => 'Y', 'tenant_id' => $this->tenantB->id])))
        ->toThrow(MassAssignmentException::class);
});

it('returns 404 when switching to a branch of another tenant', function (): void {
    $foreign = mainBranch($this->tenantB);

    $this->actingAs($this->ownerA)
        ->post(route('admin.branch.switch'), ['branch_id' => $foreign->id])
        ->assertNotFound();
});

it('returns 404 when a non-owner switches to a branch they are not assigned to', function (): void {
    $second = makeBranch($this->tenantA);
    $receptionist = makeUser($this->tenantA, RoleName::Receptionist);

    $this->actingAs($receptionist)
        ->post(route('admin.branch.switch'), ['branch_id' => $second->id])
        ->assertNotFound();

    $this->actingAs($this->ownerA)
        ->post(route('admin.branch.switch'), ['branch_id' => $second->id])
        ->assertRedirect();
});

it('resolves the tenant from the subdomain and rejects users of another tenant', function (): void {
    $this->actingAs($this->ownerA)
        ->get('http://alpha.dtmedix.test/admin')
        ->assertOk();

    $this->actingAs($this->ownerA)
        ->get('http://beta.dtmedix.test/admin')
        ->assertNotFound();
});

it('returns 404 for an unknown tenant subdomain', function (): void {
    $this->get('http://unknown.dtmedix.test/login')->assertNotFound();
});

it('lists only the own tenant branches in the API', function (): void {
    makeBranch($this->tenantB, 'BR05');
    $token = $this->ownerA->createToken('test', ['staff'])->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk();

    expect(collect($response->json('data.branches'))->pluck('code')->all())->toBe(['BR01'])
        ->and($response->json('data.tenant.id'))->toBe($this->tenantA->id);
});

it('restores the tenant context inside queued jobs', function (): void {
    config(['queue.default' => 'database']);
    CaptureTenantJob::$captured = null;

    inTenant($this->tenantB, fn () => Bus::dispatch(new CaptureTenantJob));
    app(TenantContext::class)->set(null);

    $this->artisan('queue:work', ['--once' => true, '--queue' => 'default'])->assertSuccessful();

    expect(CaptureTenantJob::$captured)->toBe($this->tenantB->id);
});

it('stores the tenant id in every queued job payload', function (): void {
    config(['queue.default' => 'database']);

    inTenant($this->tenantA, fn () => Bus::dispatch(new SendSmsJob('8801700000000', 'Test')));

    $payload = json_decode((string) DB::table('jobs')->value('payload'), true);

    expect($payload['tenant_id'])->toBe($this->tenantA->id);
});

it('prefixes cache keys and storage paths with the tenant', function (): void {
    $context = app(TenantContext::class);

    $key = $context->run($this->tenantA, fn () => $context->key('dashboard'));
    $path = $context->run($this->tenantA, fn () => $context->path('reports/1.pdf'));

    expect($key)->toBe("tenant:{$this->tenantA->id}:dashboard")
        ->and($path)->toBe("tenants/{$this->tenantA->id}/reports/1.pdf");
});

it('isolates the patient access log by tenant', function (): void {
    inTenant($this->tenantA, fn () => PatientAccessLog::query()->create(['patient_id' => 1, 'action' => 'view']));

    expect(inTenant($this->tenantB, fn () => PatientAccessLog::query()->count()))->toBe(0)
        ->and(inTenant($this->tenantA, fn () => PatientAccessLog::query()->count()))->toBe(1);
});

it('isolates the activity log by tenant', function (): void {
    inTenant($this->tenantA, fn () => activity()->log('something'));

    expect(inTenant($this->tenantB, fn () => Activity::query()->where('description', 'something')->count()))->toBe(0)
        ->and(inTenant($this->tenantA, fn () => Activity::query()->where('description', 'something')->count()))->toBe(1);
});

it('does not resolve a tenant for the central domain without a user', function (): void {
    expect(ResolveTenant::tenantFromHost('localhost'))->toBeNull()
        ->and(ResolveTenant::tenantFromHost('alpha.dtmedix.test'))->toBeInstanceOf(Tenant::class);
});
