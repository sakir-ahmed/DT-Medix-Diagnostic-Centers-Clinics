<?php

declare(strict_types=1);

namespace Tests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
    }

    /**
     * Each HTTP call in a test is a fresh request: reset the tenant context
     * so a previous request never leaks into the next one.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app->make(TenantContext::class)->set(null);

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}
