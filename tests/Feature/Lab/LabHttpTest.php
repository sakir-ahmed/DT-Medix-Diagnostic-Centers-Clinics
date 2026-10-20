<?php

declare(strict_types=1);

use App\Actions\Billing\CreateOrderAction;
use App\Actions\Catalog\SaveTestAction;
use App\Actions\Center\SeedCenterDefaultsAction;
use App\Actions\Lab\CollectSampleAction;
use App\Actions\Lab\ReceiveSampleAction;
use App\Actions\Lab\SaveResultsAction;
use App\Enums\OrderItemStatus;
use App\Enums\RoleName;
use App\Enums\SampleStatus;
use App\Models\Department;
use App\Models\NarrativeResult;
use App\Models\OrderItem;
use App\Models\Patient;
use App\Models\ResultAttachment;
use App\Models\Sample;
use App\Models\TestResult;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

beforeEach(function (): void {
    $this->center = makeCenter();
    value(fn () => app(SeedCenterDefaultsAction::class)->execute());
    $this->branch = mainBranch();
    $this->other = makeBranch('BR02');
    $this->department = value(fn () => Department::query()->where('code', 'BIO')->firstOrFail());
    $this->collector = makeUser(RoleName::SampleCollector);
    $this->technician = makeUser(RoleName::LabTechnician);
    $this->pathologist = makeUser(RoleName::Pathologist);
    $this->receptionist = makeUser(RoleName::Receptionist);
    $this->patient = value(fn () => Patient::factory()->create(['name' => 'Rupali Begum']));

    $this->test = value(fn () => app(SaveTestAction::class)->execute([
        'code' => 'FBS',
        'name' => 'Fasting blood sugar',
        'department_id' => $this->department->id,
        'price' => '150',
        'tat_hours' => 2,
        'sample_type' => 'plasma',
        'container' => 'fluoride',
        'parameters' => [
            [
                'code' => 'GLU', 'name' => 'Glucose (fasting)', 'unit' => 'mmol/L', 'result_type' => 'number',
                'ranges' => [['sex' => 'any', 'age_min' => 0, 'age_max' => null, 'age_unit' => 'years', 'low' => '3.9', 'high' => '6.1', 'critical_low' => '2.5', 'critical_high' => '25']],
            ],
        ],
    ]));

    $this->imaging = value(fn () => app(SaveTestAction::class)->execute([
        'code' => 'XR',
        'name' => 'X-ray chest',
        'department_id' => Department::query()->where('code', 'IMG')->firstOrFail()->id,
        'price' => '500',
        'tat_hours' => 4,
        'sample_type' => 'none',
        'container' => 'none',
    ]));
});

/**
 * An order for the patient, with the sample taken to the given state.
 */
function labOrderAt(string $state): OrderItem
{
    return value(function () use ($state): OrderItem {
        $order = app(CreateOrderAction::class)->execute(test()->branch, [
            'patient_id' => test()->patient->id,
            'test_ids' => [test()->test->id],
        ], test()->receptionist);

        $sample = $order->samples->first();

        if ($state !== 'pending') {
            app(CollectSampleAction::class)->execute($sample, test()->collector);
        }

        if ($state === 'received') {
            app(ReceiveSampleAction::class)->execute($sample->fresh(), test()->technician);
        }

        return $sample->fresh()->items->first();
    });
}

it('renders the lab screens for the right roles only', function (): void {
    $item = value(fn () => labOrderAt('received'));

    $this->actingAs($this->collector)->get(route('admin.samples.collection'))->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Samples/Collection'));
    $this->actingAs($this->collector)->get(route('admin.samples.receive'))->assertForbidden();
    $this->actingAs($this->collector)->get(route('admin.lab.worklist'))->assertForbidden();

    $this->actingAs($this->technician)->get(route('admin.samples.receive'))->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Samples/Receive'));
    $this->actingAs($this->technician)->get(route('admin.lab.worklist'))->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Lab/Worklist')->has('samples'));
    $this->actingAs($this->technician)->get(route('admin.lab.results', $item))->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Lab/ResultEntry')->has('rows')->where('rows.0.code', 'GLU'));
    $this->actingAs($this->receptionist)->get(route('admin.lab.critical'))->assertForbidden();

    $this->actingAs($this->pathologist)->get(route('admin.lab.critical'))->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Lab/Critical'));
    $this->actingAs($this->receptionist)->get(route('admin.lab.worklist'))->assertForbidden();
});

it('receives a sample by scanned code and reports unknown codes', function (): void {
    $item = value(fn () => labOrderAt('collected'));
    $code = $item->sample->sample_code;

    $this->actingAs($this->technician)->post(route('admin.samples.scan'), ['code' => strtolower($code)])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(value(fn () => Sample::query()->value('status')))->toBe(SampleStatus::Received);

    $this->actingAs($this->technician)->post(route('admin.samples.scan'), ['code' => 'BR01-000000-9999'])
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('saves and submits results through the admin routes, then blocks editing', function (): void {
    $item = value(fn () => labOrderAt('received'));
    $parameter = value(fn () => $item->test->parameters->first());

    $this->actingAs($this->technician)->put(route('admin.lab.results.update', $item), [
        'results' => [$parameter->id => ['value' => '5.2', 'remarks' => '']],
    ])->assertRedirect()->assertSessionHas('success');

    expect(value(fn () => TestResult::query()->value('flag')))->toBe('N');

    $this->actingAs($this->technician)->post(route('admin.lab.results.submit', $item))
        ->assertRedirect(route('admin.lab.worklist'));

    expect(value(fn () => OrderItem::query()->value('status')))->toBe(OrderItemStatus::Entered);

    $this->actingAs($this->receptionist)->put(route('admin.lab.results.update', $item), [
        'results' => [$parameter->id => ['value' => '5.3', 'remarks' => '']],
    ])->assertForbidden();
});

it('acknowledges a critical value over HTTP before submission', function (): void {
    $item = value(fn () => labOrderAt('received'));
    $parameter = value(fn () => $item->test->parameters->first());

    $this->actingAs($this->technician)->put(route('admin.lab.results.update', $item), [
        'results' => [$parameter->id => ['value' => '30', 'remarks' => '']],
    ])->assertRedirect();

    $this->actingAs($this->technician)->post(route('admin.lab.results.submit', $item))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->actingAs($this->pathologist)->post(route('admin.lab.results.acknowledge', $item), [
        'informed_name' => 'Dr. On Duty',
    ])->assertRedirect()->assertSessionHas('success');

    $this->actingAs($this->technician)->post(route('admin.lab.results.submit', $item))
        ->assertRedirect(route('admin.lab.worklist'));

    $this->actingAs($this->pathologist)->get(route('admin.lab.critical'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('results.data', 1)->where('results.data.0.informed_name', 'Dr. On Duty'));
});

it('shows 404 across branches on the lab item routes', function (): void {
    $item = value(fn () => labOrderAt('received'));

    $branchTwo = makeUser(RoleName::LabTechnician, $this->other);

    $this->actingAs($branchTwo)->get(route('admin.lab.results', $item))->assertNotFound();
    $this->actingAs($branchTwo)->put(route('admin.lab.results.update', $item), [])->assertNotFound();
});

it('keeps the worklist inside the user branch', function (): void {
    $mine = value(fn () => labOrderAt('received'));
    $mineCode = value(fn () => Sample::query()->whereKey($mine->sample_id)->value('sample_code'));

    $foreignOrder = value(fn () => app(CreateOrderAction::class)->execute($this->other, [
        'patient_id' => $this->patient->id,
        'test_ids' => [$this->test->id],
    ], $this->receptionist));
    $foreign = $foreignOrder->samples->first();
    value(function () use ($foreign): void {
        app(CollectSampleAction::class)->execute($foreign, $this->collector);
        app(ReceiveSampleAction::class)->execute($foreign->fresh(), $this->technician);
    });

    $response = $this->actingAs($this->technician)->get(route('admin.lab.worklist'))->assertOk();

    $codes = value(fn () => Sample::query()->pluck('sample_code')->all());
    expect(count($codes))->toBe(2);

    $seen = collect($response->inertiaProps()['samples']['data'] ?? [])->pluck('sample_code')->all();
    expect($seen)->toContain($mineCode)
        ->and($seen)->not->toContain($foreign->sample_code);
});

it('serves the whole lab flow through the API', function (): void {
    $item = value(fn () => labOrderAt('pending'));
    $sample = $item->sample;
    $token = $this->technician->createToken('t', ['staff'])->plainTextToken;
    $collectorToken = $this->collector->createToken('t', ['staff'])->plainTextToken;

    $this->withToken($collectorToken)->postJson("/api/v1/branches/{$this->branch->id}/samples/{$sample->id}/collect")
        ->assertOk()->assertJsonPath('data.status', 'collected');

    $this->withToken($token)->postJson("/api/v1/branches/{$this->branch->id}/samples/{$sample->id}/receive")
        ->assertOk()->assertJsonPath('data.status', 'received');

    $this->withToken($token)->getJson("/api/v1/branches/{$this->branch->id}/lab/worklist?status=received")
        ->assertOk()->assertJsonPath('data.0.sample_code', $sample->sample_code)->assertJsonPath('meta.total', 1);

    $parameter = value(fn () => $item->test->parameters->first());

    $this->withToken($token)->getJson("/api/v1/branches/{$this->branch->id}/lab/results/{$item->id}")
        ->assertOk()->assertJsonPath('data.rows.0.code', 'GLU');

    $this->withToken($token)->putJson("/api/v1/branches/{$this->branch->id}/lab/results/{$item->id}", [
        'results' => [$parameter->id => ['value' => '2.0', 'remarks' => null]],
    ])->assertOk();

    $this->withToken($token)->postJson("/api/v1/branches/{$this->branch->id}/lab/results/{$item->id}/submit")
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'CRITICAL_NOT_ACKNOWLEDGED');

    $this->withToken($token)->postJson("/api/v1/branches/{$this->branch->id}/lab/results/{$item->id}/acknowledge-critical", [
        'informed_name' => 'Dr. On Call',
    ])->assertOk();

    $this->withToken($token)->postJson("/api/v1/branches/{$this->branch->id}/lab/results/{$item->id}/submit")
        ->assertOk()->assertJsonPath('data.status', 'entered');

    $foreignToken = $this->technician->createToken('t2', ['staff'])->plainTextToken;

    $this->withToken($foreignToken)->postJson("/api/v1/branches/{$this->other->id}/samples/{$sample->id}/receive")
        ->assertNotFound();
});

it('prints the sample label as a PDF from the API', function (): void {
    $item = value(fn () => labOrderAt('collected'));
    $token = $this->collector->createToken('t', ['staff'])->plainTextToken;

    Pdf::fake();

    $this->withToken($token)->get("/api/v1/branches/{$this->branch->id}/samples/{$item->sample_id}/label")
        ->assertOk();

    Pdf::assertRespondedWithPdf(fn ($pdf): bool => $pdf->viewName === 'prints.label-single');
});

it('uploads and downloads narrative attachments through authorized routes only', function (): void {
    $order = value(fn () => app(CreateOrderAction::class)->execute($this->branch, [
        'patient_id' => $this->patient->id,
        'test_ids' => [$this->imaging->id],
    ], $this->receptionist));
    $item = $order->samples->first()->fresh()->items->first();

    value(function () use ($item): void {
        app(CollectSampleAction::class)->execute($item->sample, $this->collector);
        app(ReceiveSampleAction::class)->execute($item->sample->fresh(), $this->technician);
        app(SaveResultsAction::class)->execute($item->fresh(), ['findings' => 'Fibrosis.', 'impression' => 'OK'], $this->technician);
    });

    expect(value(fn () => NarrativeResult::query()->value('findings')))->toBe('Fibrosis.');

    $file = UploadedFile::fake()->image('scan.jpg');

    $this->actingAs($this->technician)
        ->post(route('admin.lab.results.attachments', $item), ['file' => $file])
        ->assertRedirect()
        ->assertSessionHas('success');

    $attachment = value(fn () => ResultAttachment::query()->firstOrFail());
    expect($attachment->original_name)->toBe('scan.jpg')
        ->and($attachment->path)->toStartWith('results/');

    Storage::disk(config('tenancy.storage_disk'))->assertExists($attachment->path);

    $this->actingAs($this->technician)->get(route('admin.lab.attachments.download', $attachment))->assertOk();
    $this->actingAs($this->receptionist)->get(route('admin.lab.attachments.download', $attachment))->assertForbidden();

    $branchTwo = makeUser(RoleName::LabTechnician, $this->other);
    $this->actingAs($branchTwo)->get(route('admin.lab.attachments.download', $attachment))->assertNotFound();
});
