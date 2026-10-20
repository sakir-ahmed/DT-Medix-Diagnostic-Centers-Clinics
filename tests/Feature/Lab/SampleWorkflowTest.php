<?php

declare(strict_types=1);

use App\Actions\Billing\CreateOrderAction;
use App\Actions\Catalog\SaveTestAction;
use App\Actions\Center\SeedCenterDefaultsAction;
use App\Actions\Lab\CollectSampleAction;
use App\Actions\Lab\ReceiveSampleAction;
use App\Actions\Lab\RejectSampleAction;
use App\Enums\OrderItemStatus;
use App\Enums\RoleName;
use App\Enums\SampleStatus;
use App\Events\SampleUpdated;
use App\Exceptions\DomainException;
use App\Models\Department;
use App\Models\Order;
use App\Models\Patient;
use App\Models\Sample;
use App\Models\Test;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    $this->center = makeCenter();
    value(fn () => app(SeedCenterDefaultsAction::class)->execute());
    $this->branch = mainBranch();
    $this->other = makeBranch('BR02');
    $this->department = value(fn () => Department::query()->where('code', 'BIO')->firstOrFail());
    $this->collector = makeUser(RoleName::SampleCollector);
    $this->technician = makeUser(RoleName::LabTechnician);
    $this->receptionist = makeUser(RoleName::Receptionist);
    $this->patient = value(fn () => Patient::factory()->create(['name' => 'Jamila Akter']));
});

/**
 * @param  array<string, mixed>  $overrides
 */
function labCatalogTest(string $code = 'FBS', array $overrides = []): Test
{
    return app(SaveTestAction::class)->execute($overrides + [
        'code' => $code,
        'name' => $code.' test',
        'department_id' => test()->department->id,
        'price' => '300',
        'tat_hours' => 4,
        'sample_type' => 'blood',
        'container' => 'edta',
    ]);
}

/**
 * Places an order on the main branch and returns its first sample.
 *
 * @param  array<int, Test>  $tests
 */
function labOrder(Test ...$tests): Sample
{
    /** @var Sample $sample */
    $sample = app(CreateOrderAction::class)->execute(test()->branch, [
        'patient_id' => test()->patient->id,
        'test_ids' => array_map(fn (Test $test): int => $test->id, $tests),
    ], test()->receptionist)->samples->first();

    return $sample;
}

function collectedSample(Sample $sample): Sample
{
    return app(CollectSampleAction::class)->execute($sample, test()->collector);
}

function receivedSample(Sample $sample): Sample
{
    return app(ReceiveSampleAction::class)->execute($sample, test()->technician);
}

it('collects a pending sample and moves its tests along', function (): void {
    Event::fake(SampleUpdated::class);

    $sample = value(fn () => labOrder(labCatalogTest()));
    $sample = value(fn () => collectedSample($sample));

    expect($sample->status)->toBe(SampleStatus::Collected)
        ->and($sample->collected_by)->toBe($this->collector->id)
        ->and($sample->collected_at)->not->toBeNull()
        ->and($sample->items->first()->status)->toBe(OrderItemStatus::Collected);

    Event::assertDispatched(SampleUpdated::class, fn (SampleUpdated $event): bool => $event->sample->is($sample));
});

it('broadcasts only IDs and status, and keeps patient names out of the log', function (): void {
    Event::fake([SampleUpdated::class]);

    $sample = value(fn () => labOrder(labCatalogTest()));
    value(fn () => collectedSample($sample));

    Event::assertDispatched(SampleUpdated::class, fn (SampleUpdated $event): bool => array_keys($event->broadcastWith()) === ['sample_id', 'status']);

    $logged = value(fn () => Activity::query()->get()->toJson());
    expect($logged)->not->toContain('Jamila');
});

it('receives a collected sample but not a pending one', function (): void {
    $sample = value(fn () => labOrder(labCatalogTest()));

    value(fn () => collectedSample($sample));
    $received = value(fn () => receivedSample($sample->fresh()));

    expect($received->status)->toBe(SampleStatus::Received)
        ->and($received->received_by)->toBe($this->technician->id)
        ->and($received->items->first()->status)->toBe(OrderItemStatus::Received);

    $pending = value(fn () => labOrder(labCatalogTest('CREAT')));

    value(function () use ($pending): void {
        try {
            receivedSample($pending);
            $this->fail('Expected DomainException.');
        } catch (DomainException $exception) {
            expect($exception->errorCode)->toBe('INVALID_STATE');
        }
    });
});

it('rejects a sample with a reason and creates a recollection with a new ID', function (): void {
    $sample = value(fn () => labOrder(labCatalogTest()));

    value(function () use ($sample): void {
        collectedSample($sample);
        receivedSample($sample->fresh());
    });

    $rejected = value(fn () => app(RejectSampleAction::class)->execute($sample->fresh(), 'Hemolyzed sample', true, $this->technician));
    $replacement = value(fn () => Sample::query()->where('recollection_of_id', $rejected->id)->firstOrFail());

    expect($rejected->status)->toBe(SampleStatus::Rejected)
        ->and($rejected->reject_reason)->toBe('Hemolyzed sample')
        ->and($replacement->status)->toBe(SampleStatus::Pending)
        ->and($replacement->sample_code)->not->toBe($rejected->sample_code)
        ->and($replacement->items->first()->status)->toBe(OrderItemStatus::Ordered)
        ->and($rejected->items)->toBeEmpty();

    $logged = value(fn () => Activity::query()->where('log_name', 'lab')->where('description', 'sample.rejected')->exists());
    expect($logged)->toBeTrue();
});

it('rejects without recollection and keeps the tests on the rejected sample', function (): void {
    $sample = value(fn () => labOrder(labCatalogTest('TSH')));
    value(function () use ($sample): void {
        collectedSample($sample);
    });

    $rejected = value(fn () => app(RejectSampleAction::class)->execute($sample->fresh(), 'Wrong container', false, $this->technician));

    $replacements = value(fn () => Sample::query()->where('recollection_of_id', $rejected->id)->count());
    expect($replacements)->toBe(0)
        ->and($rejected->items)->not->toBeEmpty();
});

it('collects through the admin route and blocks roles without the permission', function (): void {
    $sample = value(fn () => labOrder(labCatalogTest()));

    $this->actingAs($this->collector)->post(route('admin.samples.collect', $sample))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($this->collector)->post(route('admin.samples.scan'), ['code' => $sample->fresh()->sample_code])
        ->assertForbidden();

    $second = value(fn () => labOrder(labCatalogTest('URE')));

    $this->actingAs($this->receptionist)->post(route('admin.samples.collect', $second))
        ->assertForbidden();
});

it('tells an owner clearly when a sample belongs to another branch, and hides it from branch users', function (): void {
    $owner = makeUser(RoleName::Owner);

    /** @var Order $foreignOrder */
    $foreignOrder = value(fn (): Order => app(CreateOrderAction::class)->execute($this->other, [
        'patient_id' => $this->patient->id,
        'test_ids' => [labCatalogTest('LFT')->id],
    ], $this->receptionist));
    $foreign = $foreignOrder->samples->first();

    value(fn () => collectedSample($foreign));

    $this->actingAs($owner)->post(route('admin.samples.scan'), ['code' => $foreign->sample_code])
        ->assertRedirect()
        ->assertSessionHas('error');

    $branchTwoCollector = makeUser(RoleName::SampleCollector, $this->other);

    $this->actingAs($branchTwoCollector)->post(route('admin.samples.collect', $foreign))
        ->assertRedirect();

    $branchOneSample = value(fn () => labOrder(labCatalogTest('ALB')));

    $this->actingAs($branchTwoCollector)->post(route('admin.samples.collect', $branchOneSample))
        ->assertNotFound();
});
