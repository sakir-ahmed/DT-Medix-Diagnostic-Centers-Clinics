<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use ReflectionClass;

/**
 * Lists the Eloquent models in app/Models.
 */
final class ModelRegistry
{
    /**
     * @return list<class-string<Model>>
     */
    public static function all(): array
    {
        $models = [];

        foreach (File::files(app_path('Models')) as $file) {
            $class = 'App\\Models\\'.$file->getFilenameWithoutExtension();

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
                continue;
            }

            /** @var class-string<Model> $class */
            $models[] = $class;
        }

        sort($models);

        return $models;
    }

    /**
     * @return list<class-string<Model>>
     */
    public static function tenantOwned(): array
    {
        return array_values(array_filter(self::all(), fn (string $class): bool => in_array(BelongsToTenant::class, class_uses_recursive($class), true)));
    }
}
