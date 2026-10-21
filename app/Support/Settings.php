<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Installation configuration in the key-value settings table, with typed
 * defaults. Values are JSON-encoded in the table so booleans, numbers and
 * strings survive the round trip. Reads are cached per request.
 */
final class Settings
{
    /**
     * Default values of every known key. The database only stores rows
     * that differ or were saved through the settings page.
     *
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        // Center profile (docs/prd.md module A)
        'center_name' => 'DT Medix Diagnostic Center',
        'center_name_bn' => null,
        'center_address' => null,
        'center_contact_name' => null,
        'center_contact_mobile' => null,
        'center_contact_email' => null,
        'center_logo_path' => null,

        // Business rules (docs/prd.md section 5)
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

    /** @var array<string, mixed>|null */
    private ?array $cache = null;

    /**
     * Whether a value was actually stored for the key (not just default).
     */
    public function has(string $key): bool
    {
        return Setting::query()->where('key', $key)->exists();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        if (array_key_exists($key, $values)) {
            return $values[$key];
        }

        return $default;
    }

    /**
     * @param  list<string>|null  $keys  Only these keys, or all.
     * @return array<string, mixed>
     */
    public function all(?array $keys = null): array
    {
        $values = $this->resolve();

        return $keys === null ? $values : array_intersect_key($values, array_flip($keys));
    }

    /**
     * The business-rule keys (everything but the center_* profile).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $keys = array_keys(array_filter(
            self::DEFAULTS,
            fn (mixed $value, string $key): bool => ! str_starts_with($key, 'center_'),
            ARRAY_FILTER_USE_BOTH,
        ));

        return $this->all($keys);
    }

    /**
     * Store one or many keys and drop the read cache.
     *
     * @param  array<string, mixed>  $values
     */
    public function set(array $values): void
    {
        $changes = [];
        $before = $this->resolve();

        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS) || $value === $before[$key]) {
                continue;
            }

            $changes[$key] = $value;
        }

        if ($changes === []) {
            return;
        }

        DB::transaction(function () use ($changes): void {
            foreach ($changes as $key => $value) {
                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => json_encode($value)],
                );
            }
        });

        $this->flush();
    }

    public function flush(): void
    {
        $this->cache = null;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolve(): array
    {
        if ($this->cache === null) {
            $stored = [];

            foreach (Setting::query()->get(['key', 'value']) as $setting) {
                $stored[$setting->key] = $setting->value === null ? null : json_decode((string) $setting->value, true);
            }

            $this->cache = array_merge(self::DEFAULTS, $stored);
        }

        return $this->cache;
    }
}
