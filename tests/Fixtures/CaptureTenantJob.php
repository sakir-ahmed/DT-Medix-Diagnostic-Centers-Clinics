<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CaptureTenantJob implements ShouldQueue
{
    use Queueable;

    public static ?int $captured = null;

    public function handle(TenantContext $context): void
    {
        self::$captured = $context->id();
    }
}
