<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Patients\RegisterPatientAction;
use App\Actions\Patients\UpdatePatientAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PatientRequest;
use App\Http\Resources\PatientResource;
use App\Http\Responses\ApiResponse;
use App\Models\Patient;
use App\Services\PatientAccessLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Patients
 */
class PatientController extends Controller
{
    /**
     * Search patients by mobile, name or patient ID (`filter[q]`).
     */
    public function index(Request $request): JsonResponse
    {
        $patients = Patient::query()
            ->search((string) $request->input('filter.q', ''))
            ->orderByDesc('id')
            ->paginate(min(100, (int) $request->query('per_page', 25)));

        return ApiResponse::success(PatientResource::collection($patients));
    }

    /**
     * Register a patient. Returns 409 DUPLICATE_PATIENT_SUSPECTED with
     * candidates unless `confirm_new` is true.
     */
    public function store(PatientRequest $request, RegisterPatientAction $register): JsonResponse
    {
        $patient = $register->execute($request->safe()->except('photo'), $request->boolean('confirm_new'), $request->user()?->getAuthIdentifier());

        return ApiResponse::success(new PatientResource($patient), __('patients.created', ['code' => $patient->patient_code]), 201);
    }

    /**
     * A patient record. Writes an access log row.
     */
    public function show(Patient $patient, PatientAccessLogger $logger): JsonResponse
    {
        $logger->log($patient->id, 'view', null, 'api');

        return ApiResponse::success(new PatientResource($patient));
    }

    public function update(PatientRequest $request, Patient $patient, UpdatePatientAction $update, PatientAccessLogger $logger): JsonResponse
    {
        $update->execute($patient, $request->safe()->except(['photo', 'confirm_new']));
        $logger->log($patient->id, 'update', null, 'api');

        return ApiResponse::success(new PatientResource($patient), __('patients.updated'));
    }
}
