<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\Tenant;

/**
 * Center details and business rules (release rule, discount limit, VAT...).
 */
final class UpdateTenantSettingsAction
{
    public const CENTER_FIELDS = ['name', 'name_bn', 'contact_mobile', 'contact_email', 'address'];

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Tenant $tenant, array $data): Tenant
    {
        $center = array_intersect_key($data, array_flip(self::CENTER_FIELDS));

        if (array_key_exists('contact_mobile', $center)) {
            $center['contact_mobile'] = normalize_bd_mobile(is_string($center['contact_mobile']) ? $center['contact_mobile'] : null);
        }

        $settings = array_intersect_key($data['settings'] ?? [], Tenant::DEFAULT_SETTINGS);
        $before = $tenant->allSettings();

        $tenant->fill($center);
        $tenant->settings = array_merge($tenant->settings ?? [], $settings);
        $tenant->save();

        $changed = array_keys(array_diff_assoc(array_map('json_encode', $tenant->allSettings()), array_map('json_encode', $before)));

        if ($changed !== []) {
            activity('settings')->performedOn($tenant)->withProperties(['changed' => $changed])->log('settings.updated');
        }

        return $tenant;
    }
}
