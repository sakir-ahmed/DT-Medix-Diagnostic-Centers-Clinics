<?php

declare(strict_types=1);

/**
 * @param  array<string, mixed>  $array
 * @return list<string>
 */
function translationKeys(array $array, string $prefix = ''): array
{
    $keys = [];

    foreach ($array as $key => $value) {
        $path = $prefix.$key;
        $keys = is_array($value) && $value !== [] ? [...$keys, ...translationKeys($value, $path.'.')] : [...$keys, $path];
    }

    return $keys;
}

it('has every translation key in both en and bn', function (): void {
    $en = glob(lang_path('en/*.php')) ?: [];
    $bn = glob(lang_path('bn/*.php')) ?: [];

    expect(array_map('basename', $en))->toEqualCanonicalizing(array_map('basename', $bn));

    foreach ($en as $file) {
        $name = basename($file);
        $enKeys = translationKeys(require $file);
        $bnKeys = translationKeys(require lang_path('bn/'.$name));

        expect(array_values(array_diff($enKeys, $bnKeys)))->toBe([], "Missing in bn/{$name}")
            ->and(array_values(array_diff($bnKeys, $enKeys)))->toBe([], "Missing in en/{$name}");
    }
});

it('uses every frontend translation key that exists in the lang files', function (): void {
    $translations = [];

    foreach (glob(lang_path('en/*.php')) ?: [] as $file) {
        $translations[basename($file, '.php')] = require $file;
    }

    $missing = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('js')));

    foreach ($iterator as $file) {
        if (! $file->isFile() || ! in_array($file->getExtension(), ['vue', 'js'], true)) {
            continue;
        }

        preg_match_all("/\\bt\\('([a-z_]+(?:\\.[a-zA-Z0-9_\\-]+)+)'/", (string) file_get_contents($file->getPathname()), $matches);

        foreach ($matches[1] as $key) {
            if (! is_string(data_get($translations, $key))) {
                $missing[] = $key.' in '.$file->getFilename();
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBe([]);
});
