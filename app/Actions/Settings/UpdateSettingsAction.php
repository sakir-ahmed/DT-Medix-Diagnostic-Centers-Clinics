<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Support\Settings;

/**
 * Saves the center profile and the business rules from the settings
 * page, logging the keys that changed.
 */
final class UpdateSettingsAction
{
    /**
     * Profile form fields mapped to their settings keys.
     *
     * @var array<string, string>
     */
    private const PROFILE_FIELDS = [
        'name' => 'center_name',
        'name_bn' => 'center_name_bn',
        'address' => 'center_address',
        'contact_name' => 'center_contact_name',
        'contact_mobile' => 'center_contact_mobile',
        'contact_email' => 'center_contact_email',
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): void
    {
        $values = [];

        foreach (self::PROFILE_FIELDS as $field => $key) {
            if (array_key_exists($field, $data)) {
                $values[$key] = $field === 'contact_mobile' ? normalize_bd_mobile(is_string($data[$field]) ? $data[$field] : null) : $data[$field];
            }
        }

        $values = array_merge($values, array_intersect_key($data['settings'] ?? [], Settings::DEFAULTS));

        $before = settings()->all();
        settings()->set($values);
        $after = settings()->all();

        $changed = array_keys(array_filter(
            $after,
            fn (mixed $value, string $key): bool => array_key_exists($key, $before) && $value !== $before[$key],
            ARRAY_FILTER_USE_BOTH,
        ));

        if ($changed !== []) {
            activity('settings')->withProperties(['changed' => $changed])->log('settings.updated');
        }
    }
}
