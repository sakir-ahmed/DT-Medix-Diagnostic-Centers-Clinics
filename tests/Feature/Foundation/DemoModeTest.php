<?php

declare(strict_types=1);

use function Pest\Laravel\get;

it('shares the demo banner contact only while demo mode is on', function (): void {
    makeCenter();

    config(['app.demo' => false]);
    get(route('home'))->assertInertia(fn ($page) => $page->where('demo', null));

    config(['app.demo' => true, 'center.demo_contact_phone' => '+880 1575-888775']);
    get(route('home'))->assertInertia(fn ($page) => $page->where('demo.phone', '+880 1575-888775'));
    get(route('login'))->assertInertia(fn ($page) => $page->where('demo.phone', '+880 1575-888775'));
});

it('refuses to reset the database when demo mode is off', function (): void {
    config(['app.demo' => false]);

    $this->artisan('demo:reset')->assertFailed();
});

it('shows the dashboard for signed in staff visiting the login page', function (): void {
    makeCenter();

    $this->actingAs(makeUser())->get(route('login'))->assertRedirect();
});
