<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Module;
use App\Enums\Plan;
use App\Enums\TenantStatus;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string|null $name_bn
 * @property string $slug
 * @property string|null $domain
 * @property Plan $plan
 * @property TenantStatus $status
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $subscription_ends_at
 * @property Carbon|null $read_only_since
 * @property Carbon|null $suspended_at
 * @property Carbon|null $scheduled_deletion_at
 * @property array<string, int|null>|null $limits
 * @property list<string>|null $modules
 * @property array<string, mixed>|null $settings
 * @property int $message_credits
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name', 'name_bn', 'slug', 'domain', 'plan', 'status', 'trial_ends_at', 'subscription_ends_at',
        'read_only_since', 'suspended_at', 'scheduled_deletion_at', 'limits', 'modules', 'settings',
        'message_credits', 'contact_name', 'contact_mobile', 'contact_email', 'address',
    ];

    /**
     * Default tenant settings. Stored values override these.
     *
     * @var array<string, mixed>
     */
    public const DEFAULT_SETTINGS = [
        'release_rule' => 'hold_until_paid',
        'discount_limit_percent' => 10,
        'vat_enabled' => false,
        'vat_percent' => 0,
        'service_charge_enabled' => false,
        'service_charge_percent' => 0,
        'allow_self_verification' => false,
        'report_link_days' => 30,
        'follow_up_days' => 7,
        'follow_up_fee_percent' => 0,
        'delta_check_percent' => 50,
        'commission_basis' => 'paid',
        'commission_default_percent' => 0,
        'commission_enabled' => true,
        'default_locale' => 'en',
        'idle_timeout_minutes' => 15,
        'tat_target_hours' => 24,
        'collected_samples_refundable' => false,
    ];

    protected function casts(): array
    {
        return [
            'plan' => Plan::class,
            'status' => TenantStatus::class,
            'trial_ends_at' => 'date',
            'subscription_ends_at' => 'date',
            'read_only_since' => 'datetime',
            'suspended_at' => 'datetime',
            'scheduled_deletion_at' => 'datetime',
            'limits' => 'array',
            'modules' => 'array',
            'settings' => 'array',
            'message_credits' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'plan', 'status', 'trial_ends_at', 'limits', 'modules', 'suspended_at'])
            ->logOnlyDirty();
    }

    /**
     * @return HasMany<Branch, $this>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->settings ?? [], $key, Arr::get(self::DEFAULT_SETTINGS, $key, $default));
    }

    /**
     * @return array<string, mixed>
     */
    public function allSettings(): array
    {
        return array_merge(self::DEFAULT_SETTINGS, $this->settings ?? []);
    }

    public function hasModule(Module|string $module): bool
    {
        $value = $module instanceof Module ? $module->value : $module;

        if ($this->status === TenantStatus::Trial) {
            return true;
        }

        return in_array($value, $this->modules ?? $this->plan->modules(), true);
    }

    /**
     * @return list<string>
     */
    public function enabledModules(): array
    {
        return array_values(array_filter(Module::values(), fn (string $module): bool => $this->hasModule($module)));
    }

    public function limit(string $key): ?int
    {
        $limits = $this->limits ?? $this->plan->limits();

        return array_key_exists($key, $limits) ? ($limits[$key] === null ? null : (int) $limits[$key]) : null;
    }

    public function isReadOnly(): bool
    {
        return $this->status === TenantStatus::ReadOnly;
    }

    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::Suspended;
    }

    public function trialDaysLeft(): ?int
    {
        if ($this->status !== TenantStatus::Trial || $this->trial_ends_at === null) {
            return null;
        }

        return max(0, (int) now()->startOfDay()->diffInDays($this->trial_ends_at, false));
    }
}
