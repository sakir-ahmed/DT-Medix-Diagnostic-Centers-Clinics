<?php

declare(strict_types=1);

use App\Actions\Patients\RegisterPatientAction;
use App\Enums\RoleName;
use App\Models\Patient;

beforeEach(function (): void {
    $this->center = makeCenter();
    $this->user = makeUser(RoleName::Receptionist);
    $action = app(RegisterPatientAction::class);
    value(function () use ($action): void {
        $action->execute(['name' => 'Rahim Uddin', 'sex' => 'male', 'age_years' => 40, 'mobile' => '01711000001'], true);
        $action->execute(['name' => 'Karim Mia', 'sex' => 'male', 'age_years' => 30, 'mobile' => '01811000002'], true);
    });
});

it('finds patients by mobile, name prefix and patient ID', function (string $term, string $expected): void {
    $names = value(fn () => Patient::query()->search($term)->pluck('name')->all());

    expect($names)->toBe([$expected]);
})->with([
    'local mobile' => ['01711000001', 'Rahim Uddin'],
    'international mobile' => ['+8801811000002', 'Karim Mia'],
    'partial mobile' => ['018110', 'Karim Mia'],
    'name prefix' => ['Rah', 'Rahim Uddin'],
    'patient id' => ['p-'.date('y').'-000002', 'Karim Mia'],
]);

it('returns lookup results as JSON for the counter', function (): void {
    $this->actingAs($this->user)->getJson(route('admin.patients.lookup', ['q' => 'Kar']))
        ->assertOk()
        ->assertJsonCount(1, 'results')
        ->assertJsonPath('results.0.label', 'Karim Mia');
});

it('searches through the API filter', function (): void {
    $token = $this->user->createToken('t', ['staff'])->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/patients?filter[q]=01711000001')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.mobile', '01711000001');
});

it('feeds the command palette', function (): void {
    $this->actingAs($this->user)->getJson(route('admin.search', ['q' => 'Rahim']))
        ->assertOk()
        ->assertJsonPath('results.0.icon', 'hospital-user');
});
