<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;

it('renders the UI kit showcase', function (): void {
    $tenant = makeTenant();

    $this->actingAs(makeUser($tenant))
        ->get(route('admin.ui-kit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/UiKit')->has('rows', 8));
});

it('returns field errors from the server and keeps the input', function (): void {
    $tenant = makeTenant();

    $this->actingAs(makeUser($tenant))
        ->from(route('admin.ui-kit'))
        ->post(route('admin.ui-kit.validate'), ['name' => 'ab', 'mobile' => '0123', 'amount' => ''])
        ->assertRedirect(route('admin.ui-kit'))
        ->assertSessionHasErrors(['name', 'mobile', 'amount'])
        ->assertSessionHasInput('name', 'ab');
});

it('explains a wrong mobile number in plain words', function (): void {
    $tenant = makeTenant();

    $this->actingAs(makeUser($tenant))
        ->post(route('admin.ui-kit.validate'), ['name' => 'Rahim', 'mobile' => '0123', 'amount' => '10'])
        ->assertSessionHasErrors(['mobile' => __('validation.custom.mobile.regex')]);
});

it('renders the friendly error page for unknown admin pages', function (): void {
    $tenant = makeTenant();

    $this->actingAs(makeUser($tenant))
        ->get('/admin/does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 404));
});

it('serves the OpenAPI document', function (): void {
    $tenant = makeTenant();

    $this->actingAs(makeUser($tenant))->get('/api-docs.json')->assertOk()->assertJsonPath('openapi', fn ($v) => str_starts_with((string) $v, '3.'));
});
