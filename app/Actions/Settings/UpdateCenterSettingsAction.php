<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\Center;

/**
 * Center details and business rules (release rule, discount limit, VAT...).
 */
final class UpdateCenterSettingsAction
{
    public const CENTER_FIELDS = ['name', 'name_bn', 'contact_mobile', 'contact_email', 'address'];

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Center $center, array $data): Center
    {
        $profile = array_intersect_key($data, array_flip(self::CENTER_FIELDS));

        if (array_key_exists('contact_mobile', $profile)) {
            $profile['contact_mobile'] = normalize_bd_mobile(is_string($profile['contact_mobile']) ? $profile['contact_mobile'] : null);
        }

        $settings = array_intersect_key($data['settings'] ?? [], Center::DEFAULT_SETTINGS);
        $before = $center->allSettings();

        $center->fill($profile);
        $center->settings = array_merge($center->settings ?? [], $settings);
        $center->save();
        forget_center();

        $changed = array_keys(array_diff_assoc(array_map('json_encode', $center->allSettings()), array_map('json_encode', $before)));

        if ($changed !== []) {
            activity('settings')->performedOn($center)->withProperties(['changed' => $changed])->log('settings.updated');
        }

        return $center;
    }
}
