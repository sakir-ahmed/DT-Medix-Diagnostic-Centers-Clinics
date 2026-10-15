<?php

declare(strict_types=1);

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\BelongsToTenant;
use App\Support\Tenancy\ModelRegistry;
use Illuminate\Support\Facades\Schema;

it('scopes every model with a tenant_id column to the tenant', function (): void {
    $missing = [];

    foreach (ModelRegistry::all() as $class) {
        $model = new $class;

        if (Schema::hasColumn($model->getTable(), 'tenant_id') && ! in_array(BelongsToTenant::class, class_uses_recursive($class), true)) {
            $missing[] = $class;
        }
    }

    expect($missing)->toBe([]);
});

it('scopes every model with a branch_id column to the branch', function (): void {
    // Models that are deliberately visible across branches of the tenant.
    $crossBranch = config('tenancy.cross_branch_models', []);
    $missing = [];

    foreach (ModelRegistry::all() as $class) {
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
