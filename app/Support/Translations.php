<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Loads every lang/{locale}/*.php file into one array for the frontend.
 */
final class Translations
{
    /**
     * @return array<string, mixed>
     */
    public static function for(string $locale): array
    {
        $loader = static function () use ($locale): array {
            $path = lang_path($locale);
            $translations = [];

            if (! File::isDirectory($path)) {
                return [];
            }

            foreach (File::files($path) as $file) {
                if ($file->getExtension() === 'php') {
                    $translations[$file->getFilenameWithoutExtension()] = require $file->getPathname();
                }
            }

            return $translations;
        };

        if (app()->isProduction()) {
            /** @var array<string, mixed> */
            return Cache::rememberForever('translations:'.$locale, $loader);
        }

        return $loader();
    }
}
