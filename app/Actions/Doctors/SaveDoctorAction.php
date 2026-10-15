<?php

declare(strict_types=1);

namespace App\Actions\Doctors;

use App\Models\Doctor;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class SaveDoctorAction
{
    public function __construct(private readonly TenantContext $context) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Doctor $doctor = null, ?UploadedFile $signature = null): Doctor
    {
        $doctor ??= new Doctor;
        $data['mobile'] = normalize_bd_mobile(isset($data['mobile']) && is_string($data['mobile']) ? $data['mobile'] : null);
        unset($data['signature']);

        $doctor->fill($data)->save();

        if ($signature !== null) {
            $disk = Storage::disk((string) config('tenancy.storage_disk'));

            if ($doctor->signature_path !== null) {
                $disk->delete($doctor->signature_path);
            }

            $path = $signature->storeAs($this->context->path("doctors/{$doctor->id}"), 'signature-'.now()->timestamp.'.'.$signature->extension(), [
                'disk' => (string) config('tenancy.storage_disk'),
            ]);
            $doctor->forceFill(['signature_path' => $path === false ? null : $path])->save();
        }

        return $doctor;
    }
}
