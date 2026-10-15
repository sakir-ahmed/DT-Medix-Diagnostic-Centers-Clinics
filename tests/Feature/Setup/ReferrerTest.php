<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\Referrer;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->tenant = makeTenant();
    $this->owner = makeUser($this->tenant, RoleName::Owner);
});

it('creates a referrer with an encrypted payment account', function (): void {
    $this->actingAs($this->owner)->post(route('admin.referrers.store'), [
        'type' => 'doctor', 'code' => 'R001', 'name' => 'Dr. Karim', 'mobile' => '01811000000', 'payment_method' => 'bkash', 'payment_account' => '01811000000',
    ])->assertSessionHasNoErrors();

    expect(DB::table('referrers')->value('payment_account'))->not->toBe('01811000000')
        ->and(inTenant($this->tenant, fn () => Referrer::query()->firstOrFail()->payment_account))->toBe('01811000000');
});

it('shows payment details only to roles with commission access in the API', function (): void {
    inTenant($this->tenant, fn () => Referrer::factory()->create(['payment_account' => '01811111111']));
    $receptionist = makeUser($this->tenant, RoleName::Receptionist);

    $this->withToken($receptionist->createToken('t', ['staff'])->plainTextToken)->getJson('/api/v1/referrers')->assertOk()->assertJsonPath('data.0.payment_account', null);
    $this->withToken($this->owner->createToken('t', ['staff'])->plainTextToken)->getJson('/api/v1/referrers')->assertOk()->assertJsonPath('data.0.payment_account', '01811111111');
});

it('forbids receptionists from managing referrers', function (): void {
    $this->actingAs(makeUser($this->tenant, RoleName::Receptionist))->post(route('admin.referrers.store'), ['type' => 'doctor', 'name' => 'X'])->assertForbidden();
});

it('returns 404 for referrers of another tenant', function (): void {
    $foreign = inTenant(makeTenant(), fn () => Referrer::factory()->create());

    $this->actingAs($this->owner)->put(route('admin.referrers.update', $foreign), ['type' => 'doctor', 'name' => 'X'])->assertNotFound();
    $this->withToken($this->owner->createToken('t', ['staff'])->plainTextToken)->putJson("/api/v1/referrers/{$foreign->id}", ['type' => 'doctor', 'name' => 'X'])->assertNotFound();
});

it('shows the access log to the owner only', function (): void {
    $this->actingAs($this->owner)->get(route('admin.access-logs.index'))->assertOk();
    $this->actingAs(makeUser($this->tenant, RoleName::BranchManager))->get(route('admin.access-logs.index'))->assertForbidden();
});
