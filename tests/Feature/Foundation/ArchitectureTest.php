<?php

declare(strict_types=1);

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Every Eloquent model class under app/Models.
 *
 * @return list<class-string<Model>>
 */
function appModels(): array
{
    $classes = [];

    foreach (File::allFiles(app_path('Models')) as $file) {
        $class = 'App\\Models\\'.Str::of($file->getRelativePathname())->replace('/', '\\')->beforeLast('.php')->toString();

        if (class_exists($class) && is_subclass_of($class, Model::class) && ! (new ReflectionClass($class))->isAbstract()) {
            $classes[] = $class;
        }
    }

    return $classes;
}

it('scopes every model with a branch_id column to the branch', function (): void {
    makeCenter();
    // Models that are deliberately visible across branches of the center.
    $crossBranch = config('center.cross_branch_models', []);
    $missing = [];

    foreach (appModels() as $class) {
        $model = new $class;

        if (in_array($class, $crossBranch, true)) {
            continue;
        }

        if (Schema::hasColumn($model->getTable(), 'branch_id') && ! in_array(BelongsToBranch::class, class_uses_recursive($class), true)) {
            $missing[] = $class;
        }
    }

    expect($missing)->toBe([]);
});

it('never calls env() outside config files', function (): void {
    $offenders = [];

    foreach (['app', 'routes', 'database', 'resources/views'] as $dir) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($dir))) as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.php') && preg_match('/[^>:\w]env\(/', (string) file_get_contents($file->getPathname())) === 1) {
                $offenders[] = $file->getPathname();
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('declares strict types in every app class', function (): void {
    $offenders = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())) as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.php') && ! str_contains((string) file_get_contents($file->getPathname()), 'declare(strict_types=1);')) {
            $offenders[] = $file->getPathname();
        }
    }

    expect($offenders)->toBe([]);
});

it('never disables SSL verification', function (): void {
    $offenders = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path())) as $file) {
        if ($file->isFile() && preg_match("/'verify'\s*=>\s*false|withoutVerifying\(/", (string) file_get_contents($file->getPathname())) === 1) {
            $offenders[] = $file->getPathname();
        }
    }

    expect($offenders)->toBe([]);
});
