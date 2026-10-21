<?php

declare(strict_types=1);
use App\Support\Money;
use App\Support\Settings;

if (! function_exists('normalize_bd_mobile')) {
    /**
     * Normalize a Bangladeshi mobile number to 8801XXXXXXXXX.
     */
    function normalize_bd_mobile(?string $mobile): ?string
    {
        if ($mobile === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $mobile) ?? '';

        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '88')) {
            $digits = substr($digits, 2);
        }

        if (preg_match('/^01[3-9]\d{8}$/', $digits) !== 1) {
            return null;
        }

        return '88'.$digits;
    }
}

if (! function_exists('settings')) {
    /**
     * The installation settings service (key-value, typed defaults).
     */
    function settings(): Settings
    {
        return app(Settings::class);
    }
}

if (! function_exists('setting')) {
    /**
     * Read one configuration value.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return settings()->get($key, $default);
    }
}

if (! function_exists('forget_settings')) {
    /**
     * Drop the cached settings after direct database changes (tests).
     */
    function forget_settings(): void
    {
        settings()->flush();
    }
}

if (! function_exists('money')) {
    /**
     * Format a decimal string for storage and API output ("1250.00").
     */
    function money(string|int|float|null $amount): string
    {
        return Money::of(is_float($amount) ? (string) $amount : $amount);
    }
}
