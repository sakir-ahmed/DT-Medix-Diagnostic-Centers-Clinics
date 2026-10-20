<?php

declare(strict_types=1);

namespace App\Actions\Patients;

use App\Models\Patient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Patient photos live on the private disk under patients/{id}.
 */
final class StorePatientPhotoAction
{
    public function execute(Patient $patient, UploadedFile $photo): Patient
    {
        $disk = Storage::disk((string) config('center.storage_disk'));

        if ($patient->photo_path !== null) {
            $disk->delete($patient->photo_path);
        }

        $path = $photo->storeAs(
            "patients/{$patient->id}",
            'photo-'.now()->timestamp.'.'.$photo->extension(),
            ['disk' => (string) config('center.storage_disk')],
        );

        $patient->forceFill(['photo_path' => $path === false ? null : $path])->save();

        return $patient;
    }
}
