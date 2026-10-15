<?php

declare(strict_types=1);

namespace App\Actions\Tenants;

use App\Models\Tenant;
use App\Support\Tenancy\ModelRegistry;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Exports every tenant-owned record (JSON per model) plus the tenant's
 * private files into one zip on the private disk:
 * tenants/{id}/exports/export-{timestamp}.zip
 */
final class ExportTenantDataAction
{
    public function __construct(private readonly TenantContext $context) {}

    public function execute(Tenant $tenant): string
    {
        return $this->context->run($tenant, function () use ($tenant): string {
            $disk = Storage::disk((string) config('tenancy.storage_disk'));
            $relative = $this->context->path('exports/export-'.now()->format('Ymd-His').'.zip');
            $disk->makeDirectory(dirname($relative));

            $zip = new ZipArchive;

            if ($zip->open($disk->path($relative), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Could not create the export archive.');
            }

            $zip->addFromString('tenant.json', (string) json_encode($tenant->only(['id', 'name', 'slug', 'plan', 'status', 'settings']), JSON_PRETTY_PRINT));

            foreach (ModelRegistry::tenantOwned() as $class) {
                $rows = [];

                /** @var class-string<Model> $class */
                $class::query()->withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->orderBy((new $class)->getKeyName())
                    ->chunk(500, function (Collection $chunk) use (&$rows): void {
                        foreach ($chunk as $model) {
                            $rows[] = $model->attributesToArray();
                        }
                    });

                $zip->addFromString('data/'.(new $class)->getTable().'.json', (string) json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            foreach ($disk->allFiles($this->context->path()) as $file) {
                if (! str_contains($file, '/exports/')) {
                    $zip->addFile($disk->path($file), 'files/'.substr($file, strlen($this->context->path()) + 1));
                }
            }

            $zip->close();

            activity('export')->performedOn($tenant)->withProperties(['path' => $relative])->log('tenant.exported');

            return $relative;
        });
    }
}
