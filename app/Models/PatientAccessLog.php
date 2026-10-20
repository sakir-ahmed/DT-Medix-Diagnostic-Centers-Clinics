<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Append-only log of every view of a patient record, report or history.
 *
 * @property int $id
 * @property int|null $user_id
 * @property int $patient_id
 * @property string $action
 * @property string $channel
 * @property string|null $ip_address
 * @property Carbon $created_at
 */
class PatientAccessLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'patient_id', 'action', 'subject_type', 'subject_id', 'channel', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
