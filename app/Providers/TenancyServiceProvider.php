<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

/**
 * Makes queued jobs, notifications and listeners tenant-aware: the tenant ID
 * is stored in every job payload and restored before the job runs.
 */
class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class);
    }

    public function boot(): void
    {
        Queue::createPayloadUsing(fn (): array => [
            'tenant_id' => $this->app->make(TenantContext::class)->id(),
        ]);

        Event::listen(JobProcessing::class, function (JobProcessing $event): void {
            $tenantId = $event->job->payload()['tenant_id'] ?? null;
            $context = $this->app->make(TenantContext::class);

            if ($tenantId === $context->id()) {
                return;
            }

            $context->set($tenantId === null ? null : Tenant::query()->find($tenantId));
        });

        Event::listen(JobProcessed::class, function (JobProcessed $event): void {
            if ($event->connectionName !== 'sync') {
                $this->app->make(TenantContext::class)->set(null);
            }
        });
    }
}
