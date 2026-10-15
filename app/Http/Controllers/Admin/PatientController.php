<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Patients\RegisterPatientAction;
use App\Actions\Patients\StorePatientPhotoAction;
use App\Actions\Patients\UpdatePatientAction;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PatientRequest;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Services\PatientAccessLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $patients = Patient::query()
            ->search($search)
            ->orderByDesc('id')
            ->paginate((int) $request->query('per_page', 25))
            ->withQueryString();

        return Inertia::render('Admin/Patients/Index', [
            'patients' => PatientResource::collection($patients),
            'filters' => ['q' => $search],
            'bloodGroups' => Patient::BLOOD_GROUPS,
        ]);
    }

    /**
     * Type-ahead search (JSON) used by the reception counter.
     */
    public function lookup(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        $patients = $search === '' ? collect() : Patient::query()->search($search)->orderByDesc('id')->limit(10)->get();

        return response()->json([
            'results' => $patients->map(fn (Patient $p): array => [
                'value' => $p->id,
                'label' => $p->name,
                'description' => trim($p->patient_code.' | '.$p->mobileDisplay().' | '.$p->ageText().' '.__('patients.sex_short.'.$p->sex->value)),
                'meta' => $p->patient_code,
            ])->values(),
        ]);
    }

    public function store(PatientRequest $request, RegisterPatientAction $register, StorePatientPhotoAction $photo): RedirectResponse
    {
        try {
            $patient = $register->execute($request->safe()->except('photo'), $request->boolean('confirm_new'), $request->user()?->getAuthIdentifier());
        } catch (DomainException $e) {
            if ($e->errorCode !== 'DUPLICATE_PATIENT_SUSPECTED') {
                throw $e;
            }

            return back()->withInput()->with('duplicates', $e->details['candidates'] ?? [])->withErrors(['duplicate' => $e->getMessage()]);
        }

        if ($request->hasFile('photo')) {
            $photo->execute($patient, $request->file('photo'));
        }

        return redirect($request->input('return_to') === 'stay' ? url()->previous() : route('admin.patients.show', $patient))
            ->with('success', __('patients.created', ['code' => $patient->patient_code]))
            ->with('created_patient_id', $patient->id);
    }

    public function show(Request $request, Patient $patient, PatientAccessLogger $logger): Response
    {
        $logger->log($patient->id, 'view');

        $family = $patient->mobile === null ? collect() : Patient::query()->where('mobile', $patient->mobile)->whereKeyNot($patient->id)->orderBy('name')->get();

        return Inertia::render('Admin/Patients/Show', [
            'patient' => (new PatientResource($patient))->resolve() + [
                'photo_url' => $patient->photo_path === null ? null : route('admin.patients.photo', $patient),
                'registered_branch' => $patient->registeredBranch?->name,
            ],
            'family' => PatientResource::collection($family)->resolve(),
            'tab' => $request->query('tab', 'overview'),
            'bloodGroups' => Patient::BLOOD_GROUPS,
        ]);
    }

    public function update(PatientRequest $request, Patient $patient, UpdatePatientAction $update, StorePatientPhotoAction $photo): RedirectResponse
    {
        $update->execute($patient, $request->safe()->except(['photo', 'confirm_new']));

        if ($request->hasFile('photo')) {
            $photo->execute($patient, $request->file('photo'));
        }

        return back()->with('success', __('patients.updated'));
    }

    public function photo(Patient $patient, PatientAccessLogger $logger): StreamedResponse
    {
        abort_if($patient->photo_path === null, 404);
        $logger->log($patient->id, 'photo');

        return Storage::disk((string) config('tenancy.storage_disk'))->response($patient->photo_path, null, [
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
