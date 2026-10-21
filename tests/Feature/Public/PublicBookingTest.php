<?php

declare(strict_types=1);

use App\Enums\DepartmentCategory;
use App\Enums\ReportStyle;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Package;
use App\Models\Patient;

use function Pest\Laravel\get;

beforeEach(function (): void {
    makeCenter();
    $this->branch = mainBranch();

    $this->doctor = Doctor::query()->create([
        'name' => 'Dr. Ayesha Rahman',
        'specialty' => 'Medicine',
        'consultation_fee' => '1000.00',
    ]);
    $this->doctor->schedules()->create([
        'branch_id' => $this->branch->id,
        'weekday' => now()->addDay()->dayOfWeek,
        'start_time' => '17:00',
        'end_time' => '21:00',
    ]);

    Doctor::query()->create([
        'name' => 'Dr. Kamrul Hasan',
        'specialty' => 'Pathology',
        'consultation_fee' => '0.00',
    ]);
});

it('lists bookable doctors and hides non-consulting staff', function (): void {
    get(route('public.doctors'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('doctors', 1)
            ->where('doctors.0.name', 'Dr. Ayesha Rahman'));
});

it('lists tests by department and health packages', function (): void {
    $department = Department::query()->create([
        'code' => 'TST', 'name' => 'Public Tests', 'category' => DepartmentCategory::Pathology, 'report_style' => ReportStyle::Table,
    ]);
    $department->tests()->create([
        'code' => 'CBC', 'name' => 'Complete Blood Count', 'price' => '350.00', 'tat_hours' => 24,
    ]);
    Package::query()->create(['code' => 'PKG1', 'name' => 'Diabetes Care', 'price' => '1500.00', 'is_active' => true])
        ->tests()->attach($department->tests()->first());

    get(route('public.tests'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('departments.0.tests', 1)
            ->where('departments.0.tests.0.name', 'Complete Blood Count')
            ->has('packages', 1)
            ->where('packages.0.name', 'Diabetes Care'));
});

it('renders the booking page with doctors', function (): void {
    get(route('public.booking'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('doctors', 1)
            ->where('booked', null));
});

it('returns free slots for a scheduled day', function (): void {
    $response = get(route('public.booking.slots', ['doctor' => $this->doctor->id, 'date' => now()->addDay()->toDateString()]))
        ->assertOk()
        ->assertJsonStructure(['slots']);

    $slots = $response->json('slots');
    expect($slots)->not->toBeEmpty()
        ->and($slots[0])->toHaveKeys(['time', 'available', 'booked'])
        ->and($slots[0]['time'])->toBe('17:00');
});

it('rejects slot lookups outside the booking window', function (): void {
    get(route('public.booking.slots', ['doctor' => $this->doctor->id, 'date' => now()->addDay()->toDateString()]))
        ->assertOk();

    get(route('public.booking.slots', ['doctor' => $this->doctor->id, 'date' => now()->addDays(20)->toDateString()]))
        ->assertSessionHasErrors('date');
});

it('books an appointment from the website for a new patient', function (): void {
    $tomorrow = now()->addDay()->toDateString();

    $response = $this->post(route('public.booking.store'), [
        'doctor_id' => $this->doctor->id,
        'date' => $tomorrow,
        'time' => '17:00',
        'name' => 'Kamal Hossain',
        'mobile' => '01711223344',
        'sex' => 'male',
        'age_years' => 34,
    ]);

    $appointment = Appointment::query()->first();

    $response->assertRedirect(route('public.booking', ['booked' => $appointment->id]));

    expect($appointment)->not->toBeNull()
        ->and($appointment->source)->toBe('online')
        ->and($appointment->branch_id)->toBe($this->branch->id)
        ->and($appointment->token_no)->toBe(1)
        ->and($appointment->patient->name)->toBe('Kamal Hossain')
        ->and(Patient::query()->count())->toBe(1);

    get(route('public.booking', ['booked' => $appointment->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('booked.token_no', 1)
            ->where('booked.patient_name', 'Kamal Hossain')
            ->where('booked.doctor_name', 'Dr. Ayesha Rahman'));
});

it('reuses the patient record for a returning visitor with the same mobile and name', function (): void {
    $existing = Patient::factory()->create([
        'name' => 'Kamal Hossain',
        'mobile' => '8801711223344',
        'sex' => 'male',
    ]);

    $this->post(route('public.booking.store'), [
        'doctor_id' => $this->doctor->id,
        'date' => now()->addDay()->toDateString(),
        'time' => '17:00',
        'name' => 'Kamal Hossain',
        'mobile' => '01711223344',
        'sex' => 'male',
        'age_years' => 34,
    ])->assertRedirect();

    expect(Patient::query()->count())->toBe(1)
        ->and(Appointment::query()->first()->patient_id)->toBe($existing->id);
});

it('rejects a slot that was just taken by someone else', function (): void {
    $payload = [
        'doctor_id' => $this->doctor->id,
        'date' => now()->addDay()->toDateString(),
        'time' => '17:00',
        'name' => 'Kamal Hossain',
        'mobile' => '01711223344',
        'sex' => 'male',
        'age_years' => 34,
    ];

    $this->post(route('public.booking.store'), $payload)->assertRedirect();
    $this->post(route('public.booking.store'), [...$payload, 'name' => 'Rafiq Islam', 'mobile' => '01822334455'])
        ->assertSessionHas('error');

    expect(Appointment::query()->count())->toBe(1);
});

it('validates the visitor details', function (): void {
    $this->post(route('public.booking.store'), [
        'doctor_id' => $this->doctor->id,
        'date' => now()->addDay()->toDateString(),
        'time' => '17:00',
        'name' => 'Kamal Hossain',
        'mobile' => '12345',
        'sex' => 'unknown',
        'age_years' => 300,
    ])->assertSessionHasErrors(['mobile', 'sex', 'age_years']);

    expect(Appointment::query()->count())->toBe(0);
});

it('does not reveal an appointment without the booking session', function (): void {
    $appointment = Appointment::query()->create([
        'branch_id' => $this->branch->id,
        'doctor_id' => $this->doctor->id,
        'patient_id' => Patient::factory()->create(['name' => 'Kamal Hossain', 'sex' => 'male'])->id,
        'appointment_date' => now()->addDay(),
        'start_time' => '17:00',
        'token_no' => 1,
        'visit_type' => 'new',
        'status' => 'booked',
        'source' => 'online',
        'fee' => '1000.00',
    ]);

    get(route('public.booking', ['booked' => $appointment->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('booked', null));
});
