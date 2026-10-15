<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Tenants\ExportTenantDataAction;
use App\Models\Tenant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExportTenantDataJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public function __construct(public readonly int $tenantId) {}

    public function handle(ExportTenantDataAction $export): void
    {
        $export->execute(Tenant::query()->findOrFail($this->tenantId));
    }
}
