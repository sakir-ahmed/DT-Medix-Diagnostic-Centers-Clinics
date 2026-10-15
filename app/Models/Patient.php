<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Sex;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * A patient of the tenant (shared across its branches). Never hard-deleted.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $registered_branch_id
 * @property string $patient_code
 * @property string $name
 * @property string|null $name_bn
 * @property Sex $sex
 * @property Carbon|null $date_of_birth
 * @property bool $dob_estimated
 * @property string|null $mobile
 * @property string|null $relation_to_mobile_owner
 * @property string|null $address
 * @property string|null $blood_group
 * @property string|null $nid
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_mobile
 * @property string|null $photo_path
 * @property string|null $allergies
 * @property string|null $notes
 * @property string $preferred_locale
 * @property bool $messages_opt_out
 * @property Carbon|null $consent_at
 * @property int|null $created_by
 * @property Carbon|null $created_at
 */
class Patient extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<PatientFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    public const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    protected $fillable = [
        'registered_branch_id', 'name', 'name_bn', 'sex', 'date_of_birth', 'dob_estimated', 'mobile', 'relation_to_mobile_owner',
        'address', 'blood_group', 'nid', 'emergency_contact_name', 'emergency_mobile', 'allergies', 'notes',
        'preferred_locale', 'messages_opt_out', 'consent_at',
    ];

    protected $hidden = ['nid', 'photo_path'];

    protected function casts(): array
    {
        return [
            'sex' => Sex::class,
            'date_of_birth' => 'date',
            'dob_estimated' => 'boolean',
            'nid' => 'encrypted',
            'messages_opt_out' => 'boolean',
            'consent_at' => 'datetime',
        ];
    }

    /**
     * Only non-identifying fields go to the activity log.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['patient_code', 'sex', 'blood_group', 'messages_opt_out'])->logOnlyDirty();
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function registeredBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'registered_branch_id')->withoutGlobalScopes();
    }

    /**
     * Search by patient ID, mobile or name (prefix match so indexes are used).
     *
     * @param  Builder<Patient>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        if (preg_match('/^P-\d{2}-\d+$/i', $term) === 1) {
            $query->where('patient_code', strtoupper($term));

            return;
        }

        $digits = preg_replace('/\D+/', '', $term) ?? '';

        if ($digits !== '' && strlen($digits) >= 4 && strlen($digits) === strlen(preg_replace('/[\s+\-]/', '', $term) ?? '')) {
            $mobile = normalize_bd_mobile($digits);
            $mobile !== null
                ? $query->where('mobile', $mobile)
                : $query->where('mobile', 'like', '880'.ltrim($digits, '0').'%');

            return;
        }

        $query->where(function (Builder $where) use ($term): void {
            $where->where('name', 'like', $term.'%')
                ->orWhere('name_bn', 'like', $term.'%');
        });
    }

    /**
     * "32 Y 4 M" (localized).
     */
    public function ageText(?Carbon $at = null): string
    {
        if ($this->date_of_birth === null) {
            return '';
        }

        $at ??= now();
        $diff = $this->date_of_birth->diff($at);

        if ($diff->y < 1) {
            return $diff->m < 1
                ? __('patients.age_days', ['days' => $diff->days])
                : __('patients.age_months', ['months' => $diff->m]);
        }

        return __('patients.age_years_months', ['years' => $diff->y, 'months' => $diff->m]);
    }

    public function ageYears(?Carbon $at = null): ?int
    {
        return $this->date_of_birth === null ? null : (int) $this->date_of_birth->diffInYears($at ?? now());
    }

    /**
     * Mobile in local format 01XXXXXXXXX.
     */
    public function mobileDisplay(): ?string
    {
        return $this->mobile === null ? null : (str_starts_with($this->mobile, '88') ? substr($this->mobile, 2) : $this->mobile);
    }
}
