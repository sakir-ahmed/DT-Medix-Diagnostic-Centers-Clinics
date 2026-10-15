<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PatientAccessLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Writes a row for every view of a patient record, report or history.
 */
final class PatientAccessLogger
{
    public function __construct(private readonly Request $request) {}

    public function log(int $patientId, string $action, ?Model $subject = null, string $channel = 'staff', ?int $userId = null): PatientAccessLog
    {
        return PatientAccessLog::query()->create([
            'user_id' => $userId ?? ($channel === 'staff' ? $this->request->user()?->getAuthIdentifier() : null),
            'patient_id' => $patientId,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'channel' => $channel,
            'ip_address' => $this->request->ip(),
            'user_agent' => mb_substr((string) $this->request->userAgent(), 0, 255),
        ]);
    }
}
