<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:reset')]
#[Description('Rebuild the demo database with fresh demo data (demo mode only, never in production)')]
class ResetDemoCommand extends Command
{
    public function handle(): int
    {
        if (! config('app.demo') || app()->isProduction()) {
            $this->error('Demo reset is only allowed while IS_DEMO is on outside production.');

            return self::FAILURE;
        }

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
        $this->call('cache:clear');

        return self::SUCCESS;
    }
}
