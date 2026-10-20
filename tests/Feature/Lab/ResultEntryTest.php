<?php

declare(strict_types=1);

use App\Actions\Billing\CreateOrderAction;
use App\Actions\Catalog\SaveTestAction;
use App\Actions\Lab\AcknowledgeCriticalAction;
use App\Actions\Lab\CollectSampleAction;
use App\Actions\Lab\ReceiveSampleAction;
use App\Actions\Lab\SaveResultsAction;
use App\Actions\Lab\SubmitResultsAction;
use App\Actions\Tenants\SeedTenantDefaultsAction;
use App\Enums\DepartmentCategory;
use App\Enums\OrderItemStatus;
use App\Enums\RoleName;
use App\Events\CriticalResultRaised;
use App\Events\ResultSubmitted;
use App\Exceptions\DomainException;
use App\Models\Activity;
use App\Models\Department;
use App\Models\NarrativeResult;
use App\Models\OrderItem;
use App\Models\Patient;
use App\Models\Test;
use App\Models\TestResult;
use App\Services\Lab\ResultRows;

beforeEach(function (): void {
    $this->tenant = makeTenant();
    inTenant($this->tenant, fn () => app(SeedTenantDefaultsAction::class)->execute());
    $this->branch = mainBranch($this->tenant);
    $this->department = inTenant($this->tenant, fn () => Department::query()->where('code', 'BIO')->firstOrFail());
    $this->technician = makeUser($this->tenant, RoleName::LabTechnician);
    $this->receptionist = makeUser($this->tenant, RoleName::Receptionist);
    $this->pathologist = makeUser($this->tenant, RoleName::Pathologist);
    $this->patient = inTenant($this->tenant, fn () => Patient::factory()->create(['name' => 'Sohel Rana', 'sex' => 'male', 'date_of_birth' => now()->subYears(40)]));
});

/**
 * A parameter test with a numeric range, a choice parameter and a
 * calculated one (mirrors the demo catalog's lipid profile).
 */
function resultCatalogTest(): Test
{
    return app(SaveTestAction::class)->execute([
        'code' => 'LIP',
        'name' => 'Lipid profile',
        'department_id' => test()->department->id,
        'price' => '1200',
        'tat_hours' => 6,
        'sample_type' => 'serum',
        'container' => 'gel',
        'parameters' => [
            [
                'code' => 'TC', 'name' => 'Total cholesterol', 'unit' => 'mg/dL', 'result_type' => 'number',
                'ranges' => [['sex' => 'any', 'age_min' => 0, 'age_max' => null, 'age_unit' => 'years', 'low' => '0', 'high' => '200', 'critical_high' => '500']],
            ],
            [
                'code' => 'HDL', 'name' => 'HDL cholesterol', 'unit' => 'mg/dL', 'result_type' => 'number',
                'ranges' => [['sex' => 'any', 'age_min' => 0, 'age_max' => null, 'age_unit' => 'years', 'low' => '40', 'high' => '90', 'critical_low' => '15']],
            ],
            [
                'code' => 'TG', 'name' => 'Triglycerides', 'unit' => 'mg/dL', 'result_type' => 'number',
                'ranges' => [['sex' => 'any', 'age_min' => 0, 'age_max' => null, 'age_unit' => 'years', 'low' => '0', 'high' => '150']],
            ],
            [
                'code' => 'LDL', 'name' => 'LDL cholesterol (calculated)', 'unit' => 'mg/dL', 'result_type' => 'calculated',
                'formula' => '{TC} - {HDL} - {TG} / 5', 'decimals' => 0,
                'ranges' => [['sex' => 'any', 'age_min' => 0, 'age_max' => null, 'age_unit' => 'years', 'low' => '0', 'high' => '130']],
            ],
            [
                'code' => 'APPE', 'name' => 'Appearance', 'unit' => null, 'result_type' => 'choice',
                'choices' => ['Clear', 'Turbid'], 'abnormal_values' => ['Turbid'],
            ],
        ],
    ]);
}

/**
 * Places an order, collects and receives its sample, and returns the item.
 */
function receivedItem(Test $test): OrderItem
{
    $order = app(CreateOrderAction::class)->execute(test()->branch, [
        'patient_id' => test()->patient->id,
        'test_ids' => [$test->id],
    ], test()->receptionist);

    $sample = $order->samples->first();
    app(CollectSampleAction::class)->execute($sample, test()->receptionist);
    app(ReceiveSampleAction::class)->execute($sample->fresh(), test()->technician);

    return $sample->fresh()->items->first();
}

/**
 * @param  array<int, string|null>  $valuesByKey
 * @return array<int, array{value?: string|null, remarks?: string|null}>
 */
function resultsPayload(Test $test, array $valuesByKey): array
{
    $parameters = $test->parameters;

    return $parameters
        ->mapWithKeys(fn ($parameter) => [$parameter->id => ['value' => $valuesByKey[$parameter->code] ?? null, 'remarks' => null]])
        ->all();
}

it('stores value, range and flag snapshots with server-computed flags', function (): void {
    $test = inTenant($this->tenant, fn () => resultCatalogTest());
    $item = inTenant($this->tenant, fn () => receivedItem($test));

    inTenant($this->tenant, fn () => app(SaveResultsAction::class)->execute($item, [
        'results' => resultsPayload($test, ['TC' => '220', 'HDL' => '38', 'TG' => '100', 'APPE' => 'Turbid']),
    ], $this->technician));

    $results = inTenant($this->tenant, fn () => TestResult::query()->get()->keyBy('code'));

    expect($results['TC']->flag)->toBe('H')
        ->and($results['TC']->range_text)->toBe('0 - 200')
        ->and($results['TC']->range_high)->toBe('200.0000')
        ->and($results['HDL']->flag)->toBe('L')
        ->and($results['APPE']->flag)->toBe('A')
        ->and($results['LDL']->value)->toBe('162')
        ->and($results['LDL']->flag)->toBe('H')
        ->and($results['LDL']->result_type)->toBe('calculated')
        ->and($results['TC']->entered_by)->toBe($this->technician->id)
        ->and($results['TC']->patient_id)->toBe($this->patient->id);

    inTenant($this->tenant, function (): void {
        foreach (TestResult::query()->with('parameter.ranges')->get() as $result) {
            $range = $result->parameter->ranges->first();

            if ($range !== null) {
                $range->forceFill(['high' => '999'])->save();
            }
        }
    });

    expect(inTenant($this->tenant, fn () => TestResult::query()->where('code', 'TC')->value('range_high')))->toBe('200.0000')
        ->and(inTenant($this->tenant, fn () => TestResult::query()->where('code', 'TC')->value('flag')))->toBe('H');
});

it('flags a critical value and blocks submission until acknowledged', function (): void {
    Event::fake([ResultSubmitted::class, CriticalResultRaised::class]);

    $test = inTenant($this->tenant, fn () => resultCatalogTest());
    $item = inTenant($this->tenant, fn () => receivedItem($test));

    inTenant($this->tenant, fn () => app(SaveResultsAction::class)->execute($item, [
        'results' => resultsPayload($test, ['TC' => '620', 'HDL' => '50', 'TG' => '100']),
    ], $this->technician));

    expect(inTenant($this->tenant, fn () => TestResult::query()->where('code', 'TC')->value('flag')))->toBe('C');

    inTenant($this->tenant, function () use ($item): void {
        try {
            app(SubmitResultsAction::class)->execute($item->fresh(), $this->technician);
            $this->fail('Expected CRITICAL_NOT_ACKNOWLEDGED.');
        } catch (DomainException $exception) {
            expect($exception->errorCode)->toBe('CRITICAL_NOT_ACKNOWLEDGED');
        }
    });

    inTenant($this->tenant, fn () => app(AcknowledgeCriticalAction::class)->execute($item->fresh(), 'Dr. Kamrul, on duty', $this->pathologist));

    $critical = inTenant($this->tenant, fn () => TestResult::query()->where('code', 'TC')->first());
    expect($critical->critical_informed_name)->toBe('Dr. Kamrul, on duty')
        ->and($critical->critical_ack_by)->toBe($this->pathologist->id)
        ->and($critical->critical_ack_at)->not->toBeNull()
        ->and($critical->critical_informed_at)->not->toBeNull();

    $submitted = inTenant($this->tenant, fn () => app(SubmitResultsAction::class)->execute($item->fresh(), $this->technician));

    expect($submitted->status)->toBe(OrderItemStatus::Entered);

    Event::assertDispatched(ResultSubmitted::class);
    Event::assertDispatched(CriticalResultRaised::class);

    $logged = inTenant($this->tenant, fn () => Activity::query()->where('log_name', 'lab')->where('description', 'critical.acknowledged')->exists());
    expect($logged)->toBeTrue();
});

it('requires at least one result and rejects non-numeric values', function (): void {
    $test = inTenant($this->tenant, fn () => resultCatalogTest());
    $item = inTenant($this->tenant, fn () => receivedItem($test));

    inTenant($this->tenant, function () use ($item, $test): void {
        try {
            app(SaveResultsAction::class)->execute($item, ['results' => resultsPayload($test, [])], $this->technician);
            app(SubmitResultsAction::class)->execute($item->fresh(), $this->technician);
            $this->fail('Expected RESULTS_EMPTY.');
        } catch (DomainException $exception) {
            expect($exception->errorCode)->toBe('RESULTS_EMPTY');
        }
    });

    inTenant($this->tenant, function () use ($item, $test): void {
        try {
            app(SaveResultsAction::class)->execute($item, ['results' => resultsPayload($test, ['TC' => 'high'])], $this->technician);
            $this->fail('Expected RESULT_VALUE_INVALID.');
        } catch (DomainException $exception) {
            expect($exception->errorCode)->toBe('RESULT_VALUE_INVALID');
        }
    });
});

it('keeps earlier results as previous values for the same patient', function (): void {
    $test = inTenant($this->tenant, fn () => resultCatalogTest());

    $first = inTenant($this->tenant, fn () => receivedItem($test));
    inTenant($this->tenant, fn () => app(SaveResultsAction::class)->execute($first, [
        'results' => resultsPayload($test, ['TC' => '180', 'HDL' => '55', 'TG' => '120']),
    ], $this->technician));

    $second = inTenant($this->tenant, fn () => receivedItem($test));

    $rows = inTenant($this->tenant, fn () => app(ResultRows::class)->forItem($second));
    $previous = collect($rows)->keyBy('code');

    expect($previous['TC']['previous']['value'])->toBe('180')
        ->and($previous['TC']['previous']['date'])->not->toBeNull()
        ->and($previous['APPE']['previous'])->toBeNull();
});

it('updates a draft instead of duplicating rows', function (): void {
    $test = inTenant($this->tenant, fn () => resultCatalogTest());
    $item = inTenant($this->tenant, fn () => receivedItem($test));

    inTenant($this->tenant, fn () => app(SaveResultsAction::class)->execute($item, ['results' => resultsPayload($test, ['TC' => '190'])], $this->technician));
    inTenant($this->tenant, fn () => app(SaveResultsAction::class)->execute($item->fresh(), ['results' => resultsPayload($test, ['TC' => '195'])], $this->technician));

    expect(inTenant($this->tenant, fn () => TestResult::query()->count()))->toBe($test->parameters->count())
        ->and(inTenant($this->tenant, fn () => TestResult::query()->where('code', 'TC')->value('value')))->toBe('195');

    $submitted = inTenant($this->tenant, fn () => app(SubmitResultsAction::class)->execute($item->fresh(), $this->technician));
    expect($submitted->status)->toBe(OrderItemStatus::Entered);

    inTenant($this->tenant, fn () => app(SaveResultsAction::class)->execute($item->fresh(), ['results' => resultsPayload($test, ['TC' => '196'])], $this->technician));
    expect(inTenant($this->tenant, fn () => TestResult::query()->where('code', 'TC')->value('value')))->toBe('196');
});

it('saves narrative findings and impression for a test without parameters', function (): void {
    $test = inTenant($this->tenant, fn () => app(SaveTestAction::class)->execute([
        'code' => 'USG',
        'name' => 'USG whole abdomen',
        'department_id' => Department::query()->where('category', DepartmentCategory::Imaging->value)->firstOrFail()->id,
        'price' => '1500',
        'tat_hours' => 4,
        'sample_type' => 'none',
        'container' => 'none',
    ]));

    $item = inTenant($this->tenant, fn () => receivedItem($test));

    inTenant($this->tenant, fn () => app(SaveResultsAction::class)->execute($item, [
        'findings' => 'Liver normal in size.',
        'impression' => 'Normal study.',
    ], $this->technician));

    $narrative = inTenant($this->tenant, fn () => NarrativeResult::query()->first());
    expect($narrative->findings)->toBe('Liver normal in size.')
        ->and($narrative->impression)->toBe('Normal study.');

    $rows = inTenant($this->tenant, fn () => app(ResultRows::class)->forItem($item->fresh()));
    expect($rows)->toBeEmpty()
        ->and(inTenant($this->tenant, fn () => app(ResultRows::class)->isNarrative($item->fresh())))->toBeTrue();

    $submitted = inTenant($this->tenant, fn () => app(SubmitResultsAction::class)->execute($item->fresh(), $this->technician));
    expect($submitted->status)->toBe(OrderItemStatus::Entered);
});

it('refuses results on a cancelled item and keeps the lab log free of patient names', function (): void {
    $test = inTenant($this->tenant, fn () => resultCatalogTest());
    $item = inTenant($this->tenant, fn () => receivedItem($test));

    $sample = inTenant($this->tenant, fn () => $item->fresh()->sample);
    inTenant($this->tenant, function () use ($item): void {
        $item->fresh()->forceFill(['status' => OrderItemStatus::Cancelled, 'cancelled_at' => now(), 'cancel_reason' => 'Duplicate'])->save();
    });

    expect($sample)->not->toBeNull();

    inTenant($this->tenant, function () use ($item, $test): void {
        try {
            app(SaveResultsAction::class)->execute($item->fresh(), ['results' => resultsPayload($test, ['TC' => '100'])], $this->technician);
            $this->fail('Expected INVALID_STATE.');
        } catch (DomainException $exception) {
            expect($exception->errorCode)->toBe('INVALID_STATE');
        }
    });

    $logged = inTenant($this->tenant, fn () => Activity::query()->where('log_name', 'lab')->get()->toJson());
    expect($logged)->not->toContain('Sohel');
});
