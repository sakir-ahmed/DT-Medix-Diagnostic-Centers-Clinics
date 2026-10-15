<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Tenants\ExportTenantDataAction;
use App\Models\Tenant;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tenant:export {tenant : Tenant ID or slug}')]
#[Description('Export all data and files of one tenant into a zip on the private disk')]
class ExportTenantCommand extends Command
{
    public function handle(ExportTenantDataAction $export): int
    {
        $value = (string) $this->argument('tenant');
        $tenant = Tenant::query()->where('slug', $value)->orWhere('id', ctype_digit($value) ? (int) $value : 0)->first();

        if ($tenant === null) {
            $this->error('Tenant not found.');

            return self::FAILURE;
        }

        $path = $export->execute($tenant);
        $this->info('Export written to '.$path);

        return self::SUCCESS;
    }
}
