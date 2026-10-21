<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Picks the localized name (Bengali when present) for public display.
 */
final class LocalName
{
    public static function of(string $name, ?string $nameBn): string
    {
        return app()->getLocale() === 'bn' && filled($nameBn) ? $nameBn : $name;
    }
}
