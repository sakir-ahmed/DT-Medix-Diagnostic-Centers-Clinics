<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReferrerType;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ReferrerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Outside doctor, agent or institution that refers patients. Not a user.
 *
 * @property int $id
 * @property int $tenant_id
 * @property ReferrerType $type
 * @property string|null $code
 * @property string $name
 * @property string|null $organization
 * @property string|null $specialty
 * @property string|null $mobile
 * @property string|null $email
 * @property string|null $address
 * @property string|null $payment_method
 * @property string|null $payment_account
 * @property string|null $bank_name
 * @property string|null $notes
 * @property bool $is_active
 */
class Referrer extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<ReferrerFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $fillable = ['type', 'code', 'name', 'organization', 'specialty', 'mobile', 'email', 'address', 'payment_method', 'payment_account', 'bank_name', 'notes', 'is_active'];

    protected $hidden = ['payment_account'];

    protected function casts(): array
    {
        return [
            'type' => ReferrerType::class,
            'payment_account' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'type', 'payment_method', 'is_active'])->logOnlyDirty();
    }
}
