<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\Doctor;

beforeEach(function (): void {
    config(['app.demo' => true]);
});

it('lists the seeded demo accounts on the login page in demo mode', function (): void {
    makeCenter();
    makeUser(RoleName::Owner, attributes: ['username' => 'demo.owner']);

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('demo_users', 1)->where('demo_users.0.username', 'demo.owner'));
});

it('hides the demo accounts when demo mode is off', function (): void {
    config(['app.demo' => false]);
    makeCenter();
    makeUser(RoleName::Owner, attributes: ['username' => 'demo.owner']);

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->missing('demo_users'));
});

it('signs in a demo account with one click', function (): void {
    makeCenter();
    $owner = makeUser(RoleName::Owner, attributes: ['username' => 'demo.owner']);

    $this->post(route('demo.login', $owner))
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($owner);
});

it('refuses non-demo accounts and disabled demo mode', function (): void {
    makeCenter();
    $demoUser = makeUser(RoleName::Owner, attributes: ['username' => 'demo.owner']);
    $plainUser = makeUser(RoleName::Owner, attributes: ['username' => 'something.else']);
    $superAdmin = makeUser(RoleName::SuperAdmin, attributes: ['username' => 'demo.datatech']);

    $this->post(route('demo.login', $plainUser))->assertNotFound();
    $this->post(route('demo.login', $superAdmin))->assertNotFound();
    $this->assertGuest();

    config(['app.demo' => false]);
    $this->post(route('demo.login', $demoUser))->assertNotFound();
    $this->assertGuest();
});

it('renders the public center website with tests, doctors and stats', function (): void {
    makeCenter();
    $branch = mainBranch();

    $doctor = value(fn (): Doctor => Doctor::query()->create([
        'name' => 'Dr. Ayesha Rahman', 'specialty' => 'Medicine', 'consultation_fee' => '1000.00',
    ]));

    value(function () use ($doctor, $branch): void {
        $doctor->schedules()->create(['branch_id' => $branch->id, 'weekday' => 1, 'start_time' => '17:00', 'end_time' => '21:00']);
    });

    $response = $this->get(route('home'))->assertOk();

    $center = $response->inertiaProps()['center'];
    expect($center)->not->toBeNull()
        ->and($center['stats']['doctors'])->toBe(1)
        ->and($center['doctors'][0]['name'])->toBe('Dr. Ayesha Rahman')
        ->and($center['doctors'][0]['days'])->not->toBeEmpty();
});

it('renders the product page before the center profile exists', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('center', null));
});
