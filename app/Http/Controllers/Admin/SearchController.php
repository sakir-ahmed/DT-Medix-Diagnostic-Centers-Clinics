<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Command palette search: patients by name, mobile or patient ID.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        /** @var User $user */
        $user = $request->user();
        $results = [];

        if ($q !== '' && $user->can('patients.view')) {
            foreach (Patient::query()->search($q)->orderByDesc('id')->limit(8)->get() as $patient) {
                $results[] = [
                    'id' => 'patient-'.$patient->id,
                    'label' => $patient->name.' ('.$patient->patient_code.')',
                    'group' => __('nav.patients'),
                    'icon' => 'hospital-user',
                    'href' => route('admin.patients.show', $patient),
                ];
            }
        }

        return response()->json(['results' => $results]);
    }
}
