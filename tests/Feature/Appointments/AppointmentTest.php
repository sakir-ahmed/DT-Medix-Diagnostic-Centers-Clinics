<?php

declare(strict_types=1);

use App\Actions\Tenants\SeedTenantDefaultsAction;
use App\Enums\RoleName;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;

beforeEach(function (): void {
    $this->travelTo('2026-10-16 08:00:00');
    $this->tenant = makeTenant();
    inTenant($this->tenant, fn () => app(SeedTenantDefaultsAction::class)->execute());
    $this->branch = mainBranch($this->tenant);
    $this->other = makeBranch($this->tenant, 'BR02');
    $this->receptionist = makeUser($this->tenant, RoleName::Receptionist);
    $this->doctorUser = makeUser($this->tenant, RoleName::Doctor, attributes: ['username' => 'dr.queue']);
    $this->patient = inTenant($this->tenant, fn () => Patient::factory()->create());
    $this->doctor = inTenant($this->tenant, function (): Doctor {
        $doctor = Doctor::factory()->create([
            'user_id' => $this->doctorUser->id,
            'consultation_fee' => '1000.00',
            'follow_up_fee' => '400.00',
            'follow_up_days' => 7,
            'slot_minutes' => 30,
        ]);
        $doctor->schedules()->create([
            'branch_id' => $this->branch->id,
            'weekday' => 5,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'is_active' => true,
        ]);

        return $doctor;
    });
});

function bookPayload(int $patientId, int $doctorId, string $date = '2026-10-16', string $time = '09:00'): array
{
    return [
        'patient_id' => $patientId,
        'doctor_id' => $doctorId,
        'date' => $date,
        'time' => $time,
        'source' => 'walk-in',
    ];
}

it('lists free slots and books a walk-in with the consultation fee', function (): void {
    $token = $this->receptionist->createToken('t', ['staff'])->plainTextToken;

    $this->withToken($token)->getJson("/api/v1/branches/{$this->branch->id}/doctors/{$this->doctor->id}/slots?date=2026-10-16")
        ->assertOk()
        ->assertJsonPath('data.0.time', '09:00')
        ->assertJsonPath('data.0.available', true);

    $this->withToken($token)->postJson("/api/v1/branches/{$this->branch->id}/appointments", bookPayload($this->patient->id, $this->doctor->id), ['Idempotency-Key' => 'book-1'])
        ->assertCreated()
        ->assertJsonPath('data.fee', '1000.00')
        ->assertJsonPath('data.visit_type', 'new')
        ->assertJsonPath('data.token_no', 1);

    $this->actingAs($this->receptionist)->get(route('admin.appointments.index', ['date' => '2026-10-16', 'doctor_id' => $this->doctor->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Appointments/Index')->has('appointments', 1)->where('slots.0.available', false));
});

it('charges the follow-up fee inside the follow-up window', function (): void {
    $token = $this->receptionist->createToken('t', ['staff'])->plainTextToken;
    $first = $this->withToken($token)->postJson("/api/v1/branches/{$this->branch->id}/appointments", bookPayload($this->patient->id, $this->doctor->id), ['Idempotency-Key' => 'book-first'])->assertCreated()->json('data.id');

    foreach (['arrived', 'in-consultation', 'completed'] as $status) {
        $this->withToken($token)->patchJson("/api/v1/branches/{$this->branch->id}/appointments/{$first}/status", ['status' => $status])->assertOk();
    }

    $this->withToken($token)->postJson("/api/v1/branches/{$this->branch->id}/appointments", bookPayload($this->patient->id, $this->doctor->id, '2026-10-23', '09:00'), ['Idempotency-Key' => 'book-follow'])
        ->assertCreated()
        ->assertJsonPath('data.visit_type', 'follow_up')
        ->assertJsonPath('data.fee', '400.00');
});

it('refuses a status that is not the next step', function (): void {
    $token = $this->receptionist->createToken('t', ['staff'])->plainTextToken;
    $id = $this->withToken($token)->postJson("/api/v1/branches/{$this->branch->id}/appointments", bookPayload($this->patient->id, $this->doctor->id), ['Idempotency-Key' => 'book-status'])->json('data.id');

    $this->withToken($token)->patchJson("/api/v1/branches/{$this->branch->id}/appointments/{$id}/status", ['status' => 'completed'])
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'INVALID_STATE');

    $this->withToken($token)->patchJson("/api/v1/branches/{$this->branch->id}/appointments/{$id}/status", ['status' => 'cancelled'])
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'REASON_REQUIRED');
});

it('calls the next arrived patient from the doctor queue', function (): void {
    $token = $this->receptionist->createToken('t', ['staff'])->plainTextToken;
    $id = $this->withToken($token)->postJson("/api/v1/branches/{$this->branch->id}/appointments", bookPayload($this->patient->id, $this->doctor->id), ['Idempotency-Key' => 'book-queue'])->json('data.id');
    $this->withToken($token)->patchJson("/api/v1/branches/{$this->branch->id}/appointments/{$id}/status", ['status' => 'arrived'])->assertOk();

    $this->withoutToken()->actingAs($this->doctorUser)->post(route('admin.doctor.queue.next'))->assertRedirect();

    expect(inTenant($this->tenant, fn () => Appointment::query()->findOrFail($id)->status->value))->toBe('in-consultation');

    $this->actingAs($this->doctorUser)->get(route('admin.doctor.queue'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Doctor/Queue')->where('appointments.0.status', 'in-consultation'));
});

it('hides another branch and another tenant from appointment routes', function (): void {
    $foreign = makeTenant();
    $foreignBranch = mainBranch($foreign);
    $token = $this->receptionist->createToken('t', ['staff'])->plainTextToken;

    $this->withToken($token)->getJson("/api/v1/branches/{$this->other->id}/doctors/{$this->doctor->id}/slots")->assertNotFound();
    $this->withToken($token)->postJson("/api/v1/branches/{$this->other->id}/appointments", bookPayload($this->patient->id, $this->doctor->id), ['Idempotency-Key' => 'book-other'])->assertNotFound();
    $this->withToken($token)->getJson("/api/v1/branches/{$foreignBranch->id}/doctors/{$this->doctor->id}/slots")->assertNotFound();

    $doctorToken = $this->doctorUser->createToken('d', ['staff'])->plainTextToken;
    $this->withToken($doctorToken)->getJson("/api/v1/branches/{$this->other->id}/doctor/queue")->assertNotFound();
    $this->withToken($doctorToken)->getJson("/api/v1/branches/{$foreignBranch->id}/doctor/queue")->assertNotFound();
});

it('shows visits on the patient record', function (): void {
    $token = $this->receptionist->createToken('t', ['staff'])->plainTextToken;
    $this->withToken($token)->postJson("/api/v1/branches/{$this->branch->id}/appointments", bookPayload($this->patient->id, $this->doctor->id), ['Idempotency-Key' => 'book-visit'])->assertCreated();

    $this->withoutToken()->actingAs($this->receptionist)->get(route('admin.patients.show', $this->patient))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('visits', 1)->where('visits.0.fee', '1000.00')->has('ordered_tests', 0)->has('invoices', 0));
});
