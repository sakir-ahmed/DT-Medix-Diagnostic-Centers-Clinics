<?php

declare(strict_types=1);

use App\Enums\RoleName;
use Inertia\Testing\AssertableInertia as Assert;

it('switches the language and remembers it on the user', function (): void {
    makeCenter();
    $user = makeUser(RoleName::Receptionist);

    $this->actingAs($user)->post(route('locale.update'), ['locale' => 'bn'])->assertRedirect();

    expect($user->fresh()->locale)->toBe('bn');

    $this->actingAs($user->fresh())->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('app.locale', 'bn')->where('translations.common.save', 'সেভ করুন'));
});

it('rejects unsupported locales', function (): void {
    $this->post(route('locale.update'), ['locale' => 'fr'])->assertSessionHasErrors('locale');
});

it('uses Accept-Language for the API', function (): void {
    $this->getJson('/api/v1/auth/me', ['Accept-Language' => 'bn'])
        ->assertStatus(401)
        ->assertJsonPath('error.message', __('errors.UNAUTHENTICATED', [], 'bn'));
});
