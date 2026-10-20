<?php

declare(strict_types=1);

namespace Tests;

use App\Support\BranchContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
        forget_center();
    }

    /**
     * Each HTTP call in a test is a fresh request: reset the branch context
     * and the cached center so a previous request never leaks into the next.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app->make(BranchContext::class)->reset();
        forget_center();

        // Bearer token requests authenticate from scratch, like a real API call.
        if (isset($server['HTTP_AUTHORIZATION'])) {
            $this->app['auth']->forgetGuards();
        }

        try {
            return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
        } finally {
            // Queries made directly in the test body are not branch-restricted.
            $this->app->make(BranchContext::class)->reset();
        }
    }
}
