<?php

declare(strict_types=1);
use App\Models\Center;
use App\Support\CenterCache;
use App\Support\Money;

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

if (! function_exists('center')) {
    /**
     * The one center of this installation, loaded once per request.
     */
    function center(): ?Center
    {
        return CenterCache::get();
    }
}

if (! function_exists('forget_center')) {
    /**
     * Drop the cached center after its profile or settings change.
     */
    function forget_center(): void
    {
        CenterCache::reset();
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
