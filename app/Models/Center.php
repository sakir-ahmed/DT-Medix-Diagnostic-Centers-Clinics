<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * The profile of the one center running this installation: name, address
 * and the business-rule settings. One row per database.
 *
 * @property int $id
 * @property string $name
 * @property string|null $name_bn
 * @property string|null $address
 * @property string|null $contact_name
 * @property string|null $contact_mobile
 * @property string|null $contact_email
 * @property string|null $logo_path
 * @property array<string, mixed>|null $settings
 */
class Center extends Model
{
    /**
     * Default business rules. Stored values override these.
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

    protected $fillable = [
        'name', 'name_bn', 'address', 'contact_name', 'contact_mobile', 'contact_email', 'logo_path', 'settings',
    ];

    protected static function booted(): void
    {
        static::saved(function (): void {
            forget_center();
        });
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
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
}
