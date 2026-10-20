<?php

declare(strict_types=1);

namespace App\Actions\Patients;

use App\Exceptions\DomainException;
use App\Models\Patient;
use App\Services\SequenceGenerator;
use App\Support\BranchContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Registers a patient with the next patient ID. Unless confirmed as new,
 * a duplicate (same mobile, similar name) is reported with candidates.
 */
final class RegisterPatientAction
{
    public function __construct(
        private readonly FindDuplicatePatientsAction $duplicates,
        private readonly SequenceGenerator $sequences,
        private readonly BranchContext $context,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, bool $confirmNew = false, ?int $userId = null): Patient
    {
        $data = self::normalize($data);

        if (! $confirmNew) {
            $candidates = $this->duplicates->execute($data['mobile'] ?? null, (string) $data['name']);

            if ($candidates->isNotEmpty()) {
                throw DomainException::make('DUPLICATE_PATIENT_SUSPECTED', 409, [
                    'candidates' => $candidates->map(fn (Patient $p): array => [
                        'id' => $p->id,
                        'patient_code' => $p->patient_code,
                        'name' => $p->name,
                        'sex' => $p->sex->value,
                        'age_text' => $p->ageText(),
                    ])->all(),
                ]);
            }
        }

        return DB::transaction(function () use ($data, $userId): Patient {
            $patient = new Patient($data);
            $patient->registered_branch_id = $this->context->currentBranchId();
            $patient->patient_code = $this->sequences->patientCode();
            $patient->created_by = $userId;
            $patient->consent_at ??= now();
            $patient->save();

            return $patient;
        });
    }

    /**
     * Mobile to 8801XXXXXXXXX; age in years/months to an estimated date of birth.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        foreach (['mobile', 'emergency_mobile'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = normalize_bd_mobile(is_string($data[$field]) ? $data[$field] : null);
            }
        }

        if (empty($data['date_of_birth']) && (isset($data['age_years']) || isset($data['age_months']))) {
            $years = (int) ($data['age_years'] ?? 0);
            $months = (int) ($data['age_months'] ?? 0);
            $data['date_of_birth'] = Carbon::today()->subYears($years)->subMonths($months)->toDateString();
            $data['dob_estimated'] = true;
        } elseif (! empty($data['date_of_birth'])) {
            $data['dob_estimated'] = false;
        }

        unset($data['age_years'], $data['age_months'], $data['confirm_new'], $data['photo']);

        return $data;
    }
}
