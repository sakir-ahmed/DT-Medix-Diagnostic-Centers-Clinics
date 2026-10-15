<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('contains no emojis or symbol icons in code, lang files, views or docs', function (): void {
    $pattern = '/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{25A0}-\x{25FF}\x{FE0F}]/u';
    $offenders = [];

    $finder = (new Finder)
        ->files()
        ->in(array_map('base_path', ['app', 'resources', 'lang', 'database', 'docs', 'routes', 'config']))
        ->notName(['*.sqlite', '*.png', '*.jpg', '*.woff', '*.woff2', '*.ico']);

    foreach ($finder as $file) {
        if (preg_match($pattern, $file->getContents(), $match) === 1) {
            $offenders[] = $file->getRelativePathname().' ('.bin2hex($match[0]).')';
        }
    }

    expect($offenders)->toBe([]);
});
