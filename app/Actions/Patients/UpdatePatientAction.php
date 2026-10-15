<?php

declare(strict_types=1);

namespace App\Actions\Patients;

use App\Models\Patient;

final class UpdatePatientAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Patient $patient, array $data): Patient
    {
        $data = RegisterPatientAction::normalize($data);

        if (! array_key_exists('date_of_birth', $data)) {
            unset($data['dob_estimated']);
        }

        $patient->fill($data)->save();

        return $patient;
    }
}
