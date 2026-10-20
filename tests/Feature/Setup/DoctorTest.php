<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->center = makeCenter();
    $this->manager = makeUser(RoleName::BranchManager);
});

function doctorPayload(array $overrides = []): array
{
    return $overrides + ['name' => 'Dr. Ayesha', 'bmdc_no' => 'A-12345', 'consultation_fee' => '1000', 'follow_up_fee' => '500', 'slot_minutes' => 15];
}

it('creates a doctor with a private signature image', function (): void {
    $this->actingAs($this->manager)->post(route('admin.doctors.store'), doctorPayload(['signature' => UploadedFile::fake()->image('sig.png', 300, 100)]))->assertRedirect();

    $doctor = value(fn () => Doctor::query()->firstOrFail());

    expect($doctor->consultation_fee)->toBe('1000.00')
        ->and($doctor->signature_path)->toStartWith('doctors/');
    Storage::disk('local')->assertExists($doctor->signature_path);
    $this->actingAs($this->manager)->get(route('admin.doctors.signature', $doctor))->assertOk();
});

it('keeps the BMDC number unique', function (): void {
    $this->actingAs($this->manager)->post(route('admin.doctors.store'), doctorPayload());
    $this->actingAs($this->manager)->post(route('admin.doctors.store'), doctorPayload(['name' => 'Other']))->assertSessionHasErrors('bmdc_no');
});

it('adds weekly schedules and rejects overlaps', function (): void {
    $doctor = value(fn () => Doctor::factory()->create());
    $branch = mainBranch();

    $this->actingAs($this->manager)->post(route('admin.doctors.schedules.store', $doctor), ['branch_id' => $branch->id, 'weekday' => 1, 'start_time' => '17:00', 'end_time' => '21:00'])->assertSessionHasNoErrors();
    $this->actingAs($this->manager)->post(route('admin.doctors.schedules.store', $doctor), ['branch_id' => $branch->id, 'weekday' => 1, 'start_time' => '20:00', 'end_time' => '22:00'])
        ->assertSessionHas('error_code', 'SCHEDULE_OVERLAP');
    $this->actingAs($this->manager)->post(route('admin.doctors.schedules.store', $doctor), ['branch_id' => $branch->id, 'weekday' => 1, 'start_time' => '21:00', 'end_time' => '20:00'])
        ->assertSessionHasErrors('end_time');

    expect(value(fn () => DoctorSchedule::query()->count()))->toBe(1);
});

it('adds and removes leave days', function (): void {
    $doctor = value(fn () => Doctor::factory()->create());

    $this->actingAs($this->manager)->post(route('admin.doctors.leaves.store', $doctor), ['start_date' => now()->addDay()->toDateString(), 'end_date' => now()->addDays(3)->toDateString(), 'reason' => 'Conference'])->assertSessionHasNoErrors();
    $leave = $doctor->leaves()->firstOrFail();

    $this->actingAs($this->manager)->delete(route('admin.doctors.leaves.destroy', [$doctor, $leave]))->assertRedirect();
    expect($doctor->leaves()->count())->toBe(0);
});

it('lets receptionists view doctors but not manage them', function (): void {
    $receptionist = makeUser(RoleName::Receptionist);
    $doctor = value(fn () => Doctor::factory()->create());

    $this->actingAs($receptionist)->get(route('admin.doctors.show', $doctor))->assertOk();
    $this->actingAs($receptionist)->post(route('admin.doctors.store'), doctorPayload())->assertForbidden();
});

it('hides schedules of branches the user cannot access', function (): void {
    $doctor = value(fn () => Doctor::factory()->create());
    $other = makeBranch('BR02');
    value(fn () => $doctor->schedules()->create(['branch_id' => $other->id, 'weekday' => 2, 'start_time' => '10:00', 'end_time' => '12:00']));

    $this->actingAs($this->manager)->get(route('admin.doctors.show', $doctor))
        ->assertInertia(fn ($page) => $page->has('schedules', 0));

    $this->actingAs(makeUser(RoleName::Owner))->get(route('admin.doctors.show', $doctor))
        ->assertInertia(fn ($page) => $page->has('schedules', 1));
});
