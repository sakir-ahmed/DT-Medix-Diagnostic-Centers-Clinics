<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Center;

/**
 * Request-level cache of the one center row (see the center() helper).
 */
final class CenterCache
{
    private static ?Center $center = null;

    private static bool $loaded = false;

    public static function get(): ?Center
    {
        if (! self::$loaded) {
            self::$center = Center::query()->first();
            self::$loaded = true;
        }

        return self::$center;
    }

    public static function reset(): void
    {
        self::$center = null;
        self::$loaded = false;
    }
}
