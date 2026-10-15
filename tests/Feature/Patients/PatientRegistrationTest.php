<?php

declare(strict_types=1);

use App\Actions\Patients\RegisterPatientAction;
use App\Enums\RoleName;
use App\Models\Patient;
use App\Models\PatientAccessLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->tenant = makeTenant();
    $this->receptionist = makeUser($this->tenant, RoleName::Receptionist);
});

function patientPayload(array $overrides = []): array
{
    return $overrides + ['name' => 'Rahim Uddin', 'sex' => 'male', 'age_years' => 45, 'mobile' => '01711000001'];
}

it('registers a patient with a sequential patient ID per tenant and year', function (): void {
    $this->actingAs($this->receptionist)->post(route('admin.patients.store'), patientPayload())->assertRedirect();
    $this->actingAs($this->receptionist)->post(route('admin.patients.store'), patientPayload(['name' => 'Karim', 'mobile' => '01711000002']))->assertRedirect();

    $codes = inTenant($this->tenant, fn () => Patient::query()->orderBy('id')->pluck('patient_code')->all());
    $year = now()->format('y');

    expect($codes)->toBe(["P-{$year}-000001", "P-{$year}-000002"]);

    $other = makeTenant();
    $patient = inTenant($other, fn () => app(RegisterPatientAction::class)->execute(patientPayload(), true));
    expect($patient->patient_code)->toBe("P-{$year}-000001");
});

it('never reuses a patient ID after a soft delete', function (): void {
    $action = app(RegisterPatientAction::class);
    $first = inTenant($this->tenant, fn () => $action->execute(patientPayload(), true));
    inTenant($this->tenant, fn () => $first->delete());
    $second = inTenant($this->tenant, fn () => $action->execute(patientPayload(['name' => 'Other']), true));

    expect($second->patient_code)->not->toBe($first->patient_code)
        ->and(Patient::withTrashed()->withoutGlobalScopes()->whereKey($first->id)->exists())->toBeTrue();
});

it('stores the mobile normalized and the age as an estimated date of birth', function (): void {
    $this->actingAs($this->receptionist)->post(route('admin.patients.store'), patientPayload(['age_years' => 32, 'age_months' => 4]));

    $patient = inTenant($this->tenant, fn () => Patient::query()->firstOrFail());

    expect($patient->mobile)->toBe('8801711000001')
        ->and($patient->dob_estimated)->toBeTrue()
        ->and($patient->ageText())->toBe('32 Y 4 M');
});

it('warns about a duplicate with same mobile and similar name and offers the existing record', function (): void {
    $this->actingAs($this->receptionist)->post(route('admin.patients.store'), patientPayload(['name' => 'Md. Rahim Uddin']));

    $this->actingAs($this->receptionist)
        ->post(route('admin.patients.store'), patientPayload(['name' => 'Rahim Uddin']))
        ->assertRedirect()
        ->assertSessionHasErrors('duplicate')
        ->assertSessionHas('duplicates', fn (array $candidates): bool => count($candidates) === 1 && $candidates[0]['name'] === 'Md. Rahim Uddin');

    expect(inTenant($this->tenant, fn () => Patient::query()->count()))->toBe(1);
});

it('allows family members with the same mobile and different names', function (): void {
    $this->actingAs($this->receptionist)->post(route('admin.patients.store'), patientPayload());
    $this->actingAs($this->receptionist)->post(route('admin.patients.store'), patientPayload(['name' => 'Fatema Begum', 'sex' => 'female']))->assertSessionHasNoErrors();

    expect(inTenant($this->tenant, fn () => Patient::query()->where('mobile', '8801711000001')->count()))->toBe(2);
});

it('creates the patient when a duplicate is confirmed as new', function (): void {
    $this->actingAs($this->receptionist)->post(route('admin.patients.store'), patientPayload());
    $this->actingAs($this->receptionist)->post(route('admin.patients.store'), patientPayload(['confirm_new' => true]))->assertSessionHasNoErrors();

    expect(inTenant($this->tenant, fn () => Patient::query()->count()))->toBe(2);
});

it('returns DUPLICATE_PATIENT_SUSPECTED with candidates from the API', function (): void {
    $token = $this->receptionist->createToken('t', ['staff'])->plainTextToken;
    $this->withToken($token)->postJson('/api/v1/patients', patientPayload())->assertCreated()->assertJsonPath('data.patient_code', 'P-'.now()->format('y').'-000001');

    $this->withToken($token)->postJson('/api/v1/patients', patientPayload(['name' => 'Rahim Udin']))
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'DUPLICATE_PATIENT_SUSPECTED')
        ->assertJsonCount(1, 'error.details.candidates');

    $this->withToken($token)->postJson('/api/v1/patients', patientPayload(['name' => 'Rahim Udin', 'confirm_new' => true]))->assertCreated();
});

it('validates input with clear messages', function (): void {
    $this->actingAs($this->receptionist)
        ->post(route('admin.patients.store'), ['name' => '', 'sex' => 'x', 'mobile' => '123', 'nid' => '12'])
        ->assertSessionHasErrors(['name', 'sex', 'mobile', 'nid', 'age_years']);
});

it('encrypts the NID at rest and never returns it in full', function (): void {
    $this->actingAs($this->receptionist)->post(route('admin.patients.store'), patientPayload(['nid' => '1234567890123']));

    $raw = DB::table('patients')->value('nid');
    $patient = inTenant($this->tenant, fn () => Patient::query()->firstOrFail());
    $token = $this->receptionist->createToken('t', ['staff'])->plainTextToken;

    expect($raw)->not->toContain('1234567890123')
        ->and($patient->nid)->toBe('1234567890123');

    $this->withToken($token)->getJson("/api/v1/patients/{$patient->id}")
        ->assertOk()
        ->assertJsonPath('data.nid_last4', '0123')
        ->assertJsonMissingPath('data.nid');
});

it('writes an access log row when a patient record is opened', function (): void {
    $patient = inTenant($this->tenant, fn () => app(RegisterPatientAction::class)->execute(patientPayload(), true));

    $this->actingAs($this->receptionist)
        ->get(route('admin.patients.show', $patient))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Patients/Show')->where('patient.patient_code', $patient->patient_code));

    $log = inTenant($this->tenant, fn () => PatientAccessLog::query()->firstOrFail());
    expect($log->user_id)->toBe($this->receptionist->id)
        ->and($log->patient_id)->toBe($patient->id)
        ->and($log->action)->toBe('view')
        ->and($log->ip_address)->toBe('127.0.0.1');
});

it('lists family members linked by the same mobile', function (): void {
    $action = app(RegisterPatientAction::class);
    $father = inTenant($this->tenant, fn () => $action->execute(patientPayload(), true));
    inTenant($this->tenant, fn () => $action->execute(patientPayload(['name' => 'Sumaiya', 'sex' => 'female', 'age_years' => 6]), true));

    $this->actingAs($this->receptionist)->get(route('admin.patients.show', $father))
        ->assertInertia(fn (Assert $page) => $page->has('family', 1)->where('family.0.name', 'Sumaiya'));
});

it('stores the photo on the private disk and serves it only to staff', function (): void {
    $this->actingAs($this->receptionist)->post(route('admin.patients.store'), patientPayload(['photo' => UploadedFile::fake()->image('p.jpg')]));
    $patient = inTenant($this->tenant, fn () => Patient::query()->firstOrFail());

    expect($patient->photo_path)->toStartWith("tenants/{$this->tenant->id}/patients/{$patient->id}/");
    Storage::disk('local')->assertExists($patient->photo_path);

    $this->actingAs($this->receptionist)->get(route('admin.patients.photo', $patient))->assertOk();
    auth()->logout();
    $this->get(route('admin.patients.photo', $patient))->assertRedirect(route('login'));
});

it('updates a patient', function (): void {
    $patient = inTenant($this->tenant, fn () => app(RegisterPatientAction::class)->execute(patientPayload(), true));

    $this->actingAs($this->receptionist)->post(route('admin.patients.update', $patient), ['allergies' => 'Sulfa', 'blood_group' => 'O+'])->assertSessionHasNoErrors();

    expect($patient->fresh()->allergies)->toBe('Sulfa');
});

it('forbids roles without the permission', function (): void {
    $accountant = makeUser($this->tenant, RoleName::Accountant);

    $this->actingAs($accountant)->get(route('admin.patients.index'))->assertForbidden();
    $this->actingAs($accountant)->post(route('admin.patients.store'), patientPayload())->assertForbidden();
});

it('returns 404 for a patient of another tenant on web and API', function (): void {
    $other = makeTenant();
    $foreign = inTenant($other, fn () => app(RegisterPatientAction::class)->execute(patientPayload(), true));
    $token = $this->receptionist->createToken('t', ['staff'])->plainTextToken;

    $this->actingAs($this->receptionist)->get(route('admin.patients.show', $foreign))->assertNotFound();
    $this->actingAs($this->receptionist)->post(route('admin.patients.update', $foreign), ['name' => 'X'])->assertNotFound();
    $this->withToken($token)->getJson("/api/v1/patients/{$foreign->id}")->assertNotFound()->assertJsonPath('error.code', 'NOT_FOUND');
    $this->withToken($token)->patchJson("/api/v1/patients/{$foreign->id}", ['name' => 'X'])->assertNotFound();

    expect(PatientAccessLog::query()->withoutGlobalScopes()->count())->toBe(0);
});

it('shows patients of the tenant to staff of every branch', function (): void {
    $second = makeBranch($this->tenant);
    $patient = inTenant($this->tenant, fn () => app(RegisterPatientAction::class)->execute(patientPayload(), true));
    $otherBranchUser = makeUser($this->tenant, RoleName::Receptionist, $second);

    $this->actingAs($otherBranchUser)->get(route('admin.patients.show', $patient))->assertOk();
});

it('never writes patient data to the application log', function (): void {
    Log::spy();

    $this->actingAs($this->receptionist)->post(route('admin.patients.store'), patientPayload(['name' => 'Very Unique Name']));

    Log::shouldNotHaveReceived('info', [Mockery::on(fn ($m) => str_contains((string) $m, 'Very Unique Name'))]);
    expect(true)->toBeTrue();
});
