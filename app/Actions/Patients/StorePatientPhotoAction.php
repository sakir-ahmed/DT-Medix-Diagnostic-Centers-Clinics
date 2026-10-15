<?php

declare(strict_types=1);

namespace App\Actions\Patients;

use App\Models\Patient;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Patient photos live on the private disk under the tenant directory.
 */
final class StorePatientPhotoAction
{
    public function __construct(private readonly TenantContext $context) {}

    public function execute(Patient $patient, UploadedFile $photo): Patient
    {
        $disk = Storage::disk((string) config('tenancy.storage_disk'));

        if ($patient->photo_path !== null) {
            $disk->delete($patient->photo_path);
        }

        $path = $photo->storeAs(
            $this->context->path("patients/{$patient->id}"),
            'photo-'.now()->timestamp.'.'.$photo->extension(),
            ['disk' => (string) config('tenancy.storage_disk')],
        );

        $patient->forceFill(['photo_path' => $path === false ? null : $path])->save();

        return $patient;
    }
}
