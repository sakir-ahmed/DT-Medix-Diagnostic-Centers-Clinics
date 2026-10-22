<?php

declare(strict_types=1);

use App\Models\Branch;
use App\Models\Setting;

use function Pest\Laravel\get;

dataset('site pages', [
    'about' => ['public.about', 'Public/About'],
    'services' => ['public.services', 'Public/Services'],
    'home collection' => ['public.home-collection', 'Public/HomeCollection'],
    'reports' => ['public.reports', 'Public/Reports'],
    'contact' => ['public.contact', 'Public/Contact'],
    'faq' => ['public.faq', 'Public/Faq'],
    'privacy' => ['public.privacy', 'Public/Privacy'],
]);

it('renders every website page for a configured center', function (string $route, string $component): void {
    makeCenter();

    get(route($route))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with('site pages');

it('sends visitors home when the center profile is not set up', function (string $route): void {
    Setting::query()->where('key', 'center_name')->delete();

    get(route($route))->assertRedirect(route('home'));
})->with(['public.about', 'public.services', 'public.home-collection', 'public.reports', 'public.contact', 'public.faq', 'public.privacy']);

it('shares the public contact profile and active branches', function (): void {
    makeCenter();
    settings()->set(['center_contact_mobile' => '01711000000', 'center_address' => 'Road 1, Dhaka']);
    Branch::query()->withoutGlobalScopes()->where('code', 'BR01')->update(['address' => 'Main Road', 'phone' => '01811000000']);
    makeBranch('BR02')->update(['is_active' => false]);

    get(route('public.contact'))
        ->assertInertia(fn ($page) => $page
            ->where('site.phone', '01711000000')
            ->where('site.address', 'Road 1, Dhaka')
            ->has('site.branches', 1)
            ->where('site.branches.0.address', 'Main Road'));
});

it('does not share the site profile outside public pages', function (): void {
    makeCenter();

    get(route('login'))->assertInertia(fn ($page) => $page->where('site', null));
});

it('lists home collection tests only when eligible', function (): void {
    makeCenter();

    get(route('public.home-collection'))
        ->assertInertia(fn ($page) => $page->has('tests', 0));
});

it('shows packages and stats on the home page', function (): void {
    makeCenter();

    get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('center.packages')
            ->has('center.stats.home_collection'));
});
