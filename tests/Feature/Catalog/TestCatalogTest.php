<?php

declare(strict_types=1);

use App\Actions\Catalog\SaveTestAction;
use App\Actions\Tenants\SeedTenantDefaultsAction;
use App\Enums\RoleName;
use App\Models\BranchTestPrice;
use App\Models\Department;
use App\Models\Package;
use App\Models\Test;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->tenant = makeTenant();
    inTenant($this->tenant, fn () => app(SeedTenantDefaultsAction::class)->execute());
    $this->manager = makeUser($this->tenant, RoleName::BranchManager);
    $this->department = inTenant($this->tenant, fn () => Department::query()->where('code', 'BIO')->firstOrFail());
});

function testPayload(int $departmentId, array $overrides = []): array
{
    return $overrides + [
        'code' => 'lipid', 'name' => 'Lipid Profile', 'department_id' => $departmentId, 'sample_type' => 'serum', 'container' => 'gel',
        'price' => '1200', 'tat_hours' => 6,
        'parameters' => [
            ['code' => 'TC', 'name' => 'Total cholesterol', 'unit' => 'mg/dL', 'result_type' => 'number', 'ranges' => [['sex' => 'any', 'age_min' => 0, 'age_max' => null, 'age_unit' => 'years', 'high' => '200']]],
            ['code' => 'TG', 'name' => 'Triglycerides', 'unit' => 'mg/dL', 'result_type' => 'number', 'ranges' => []],
            ['code' => 'HDL', 'name' => 'HDL', 'unit' => 'mg/dL', 'result_type' => 'number', 'ranges' => [['sex' => 'male', 'age_min' => 18, 'age_max' => null, 'age_unit' => 'years', 'low' => '40'], ['sex' => 'female', 'age_min' => 18, 'age_max' => null, 'age_unit' => 'years', 'low' => '50']]],
            ['code' => 'LDL', 'name' => 'LDL', 'unit' => 'mg/dL', 'result_type' => 'calculated', 'formula' => '{TC} - {HDL} - {TG} / 5', 'ranges' => []],
        ],
    ];
}

it('creates a test with parameters, ranges and critical limits', function (): void {
    $this->actingAs($this->manager)->post(route('admin.tests.store'), testPayload($this->department->id))->assertSessionHasNoErrors()->assertRedirect();

    $test = inTenant($this->tenant, fn () => Test::query()->with('parameters.ranges')->where('code', 'LIPID')->firstOrFail());

    expect($test->price)->toBe('1200.00')
        ->and($test->parameters->pluck('code')->all())->toBe(['TC', 'TG', 'HDL', 'LDL'])
        ->and($test->parameters[2]->ranges)->toHaveCount(2)
        ->and($test->parameters[3]->formula)->toBe('{TC} - {HDL} - {TG} / 5');
});

it('updates parameters and removes the ones left out', function (): void {
    $this->actingAs($this->manager)->post(route('admin.tests.store'), testPayload($this->department->id));
    $test = inTenant($this->tenant, fn () => Test::query()->with('parameters')->firstOrFail());

    $payload = testPayload($this->department->id);
    $payload['parameters'] = [array_merge($payload['parameters'][0], ['id' => $test->parameters[0]->id, 'name' => 'Cholesterol, total'])];

    $this->actingAs($this->manager)->put(route('admin.tests.update', $test), $payload)->assertSessionHasNoErrors();

    $test->refresh()->load('parameters');
    expect($test->parameters)->toHaveCount(1)->and($test->parameters[0]->name)->toBe('Cholesterol, total');
});

it('rejects formulas that reference unknown codes or unsafe characters', function (string $formula): void {
    $payload = testPayload($this->department->id);
    $payload['parameters'][3]['formula'] = $formula;

    $this->actingAs($this->manager)->post(route('admin.tests.store'), $payload)->assertSessionHas('error_code', 'INVALID_FORMULA');
    expect(inTenant($this->tenant, fn () => Test::query()->count()))->toBe(0);
})->with(['{TC} - {XYZ}', '{TC}; system()', 'no codes']);

it('validates ranges and duplicate codes', function (): void {
    $payload = testPayload($this->department->id);
    $payload['parameters'][1]['code'] = 'TC';
    $payload['parameters'][0]['ranges'][0]['age_max'] = 0;

    $this->actingAs($this->manager)->post(route('admin.tests.store'), $payload)
        ->assertSessionHasErrors(['parameters.0.code', 'parameters.0.ranges.0.age_max']);
});

it('stores optional branch prices used at the counter', function (): void {
    $branch = mainBranch($this->tenant);
    $payload = testPayload($this->department->id, ['branch_prices' => [$branch->id => '1100']]);

    $this->actingAs($this->manager)->post(route('admin.tests.store'), $payload)->assertSessionHasNoErrors();

    expect(inTenant($this->tenant, fn () => BranchTestPrice::query()->value('price')))->toBe('1100.00');

    $this->actingAs($this->manager)->getJson(route('admin.tests.lookup', ['q' => 'LIPID']))
        ->assertOk()->assertJsonPath('results.0.price', '1100.00');
});

it('expands a package into its tests in the counter lookup', function (): void {
    $action = app(SaveTestAction::class);
    $ids = inTenant($this->tenant, fn () => collect(['FBS', 'HBA1C', 'CREAT'])->map(fn ($code) => $action->execute(['code' => $code, 'name' => $code.' test', 'department_id' => $this->department->id, 'price' => '300', 'tat_hours' => 4])->id)->all());

    $this->actingAs($this->manager)->post(route('admin.packages.store'), ['code' => 'DIAB', 'name' => 'Diabetes Panel', 'price' => '800', 'test_ids' => $ids])->assertSessionHasNoErrors();

    $package = inTenant($this->tenant, fn () => Package::query()->with('tests')->firstOrFail());
    expect($package->tests->pluck('code')->sort()->values()->all())->toBe(['CREAT', 'FBS', 'HBA1C']);

    $this->actingAs($this->manager)->getJson(route('admin.tests.lookup', ['q' => 'Diab']))
        ->assertOk()
        ->assertJsonPath('results.0.type', 'package')
        ->assertJsonPath('results.0.price', '800.00')
        ->assertJsonCount(3, 'results.0.tests');
});

it('rejects a package with tests of another tenant', function (): void {
    $foreign = inTenant(makeTenant(), function () {
        app(SeedTenantDefaultsAction::class)->execute();

        return app(SaveTestAction::class)->execute(['code' => 'X', 'name' => 'X', 'department_id' => Department::query()->value('id'), 'price' => '1', 'tat_hours' => 1]);
    });
    $own = inTenant($this->tenant, fn () => app(SaveTestAction::class)->execute(['code' => 'Y', 'name' => 'Y', 'department_id' => $this->department->id, 'price' => '1', 'tat_hours' => 1]));

    $this->actingAs($this->manager)->post(route('admin.packages.store'), ['code' => 'P', 'name' => 'P', 'price' => '1', 'test_ids' => [$own->id, $foreign->id]])
        ->assertSessionHasErrors('test_ids.1');
});

it('lets technicians view the catalog but not change it', function (): void {
    $tech = makeUser($this->tenant, RoleName::LabTechnician);

    $this->actingAs($tech)->get(route('admin.tests.index'))->assertOk();
    $this->actingAs($tech)->post(route('admin.tests.store'), testPayload($this->department->id))->assertForbidden();
});

it('returns 404 for tests of another tenant', function (): void {
    $foreign = inTenant(makeTenant(), function () {
        app(SeedTenantDefaultsAction::class)->execute();

        return app(SaveTestAction::class)->execute(['code' => 'X', 'name' => 'X', 'department_id' => Department::query()->value('id'), 'price' => '1', 'tat_hours' => 1]);
    });
    $token = $this->manager->createToken('t', ['staff'])->plainTextToken;

    $this->actingAs($this->manager)->get(route('admin.tests.edit', $foreign))->assertNotFound();
    $this->withToken($token)->getJson("/api/v1/tests/{$foreign->id}/parameters")->assertNotFound();
});

it('manages tests and packages through the API', function (): void {
    $token = $this->manager->createToken('t', ['staff'])->plainTextToken;

    $id = $this->withToken($token)->postJson('/api/v1/tests', testPayload($this->department->id))
        ->assertCreated()
        ->assertJsonPath('data.price', '1200.00')
        ->assertJsonCount(4, 'data.parameters')
        ->json('data.id');

    $this->withToken($token)->getJson("/api/v1/tests/{$id}/parameters")->assertOk()->assertJsonPath('data.0.ranges.0.text', '< 200');
    $this->withToken($token)->getJson('/api/v1/tests?filter[q]=LIP')->assertOk()->assertJsonPath('meta.total', 1);
});

it('renders the test form page', function (): void {
    $this->actingAs($this->manager)->get(route('admin.tests.create'))
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Tests/Form')->has('departments', 7)->has('resultTypes', 4));
});
