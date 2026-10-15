<?php

declare(strict_types=1);

use App\Enums\Module;
use App\Enums\Plan;
use App\Enums\TenantStatus;
use App\Exceptions\DomainException;
use App\Services\PlanGate;

it('enforces the branch limit', function (): void {
    $tenant = makeTenant(['plan' => Plan::Starter, 'limits' => ['branches' => 1, 'users' => 5]]);

    expect(fn () => app(PlanGate::class)->ensureCanAddBranch($tenant))
        ->toThrow(fn (DomainException $e) => expect($e->errorCode)->toBe('PLAN_LIMIT_REACHED'));
});

it('enforces the user limit', function (): void {
    $tenant = makeTenant(['limits' => ['branches' => 3, 'users' => 1]]);
    makeUser($tenant);

    expect(fn () => app(PlanGate::class)->ensureCanAddUser($tenant))->toThrow(DomainException::class);
});

it('treats a null limit as unlimited', function (): void {
    $tenant = makeTenant(['limits' => ['branches' => null, 'users' => null]]);
    makeUser($tenant);

    app(PlanGate::class)->ensureCanAddUser($tenant);
    app(PlanGate::class)->ensureCanAddBranch($tenant);

    expect(true)->toBeTrue();
});

it('blocks disabled modules and allows all modules during the trial', function (): void {
    $active = makeTenant(['status' => TenantStatus::Active, 'modules' => ['patients']]);
    $trial = makeTenant(['status' => TenantStatus::Trial, 'modules' => ['patients']]);

    expect(fn () => app(PlanGate::class)->ensureModule($active, Module::Commission))
        ->toThrow(fn (DomainException $e) => expect($e->errorCode)->toBe('MODULE_DISABLED'));

    app(PlanGate::class)->ensureModule($trial, Module::Commission);
    expect($trial->hasModule(Module::Commission))->toBeTrue();
});

it('checks message credits', function (): void {
    $tenant = makeTenant(['message_credits' => 0]);

    expect(fn () => app(PlanGate::class)->ensureMessageCredits($tenant))->toThrow(DomainException::class);
});
