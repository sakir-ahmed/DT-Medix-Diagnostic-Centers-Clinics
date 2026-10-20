<?php

declare(strict_types=1);

use App\Actions\Billing\CreateOrderAction;
use App\Actions\Catalog\SaveTestAction;
use App\Actions\Tenants\SeedTenantDefaultsAction;
use App\Enums\RoleName;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

it('never duplicates an invoice number when two orders are saved together', function (): void {
    if (! function_exists('pcntl_fork')) {
        test()->markTestSkipped('pcntl is required');
    }

    $path = sys_get_temp_dir().'/dtmedix-invoice-'.getmypid().'.sqlite';
    @unlink($path);

    config([
        'database.connections.concurrency' => [
            'driver' => 'sqlite',
            'database' => $path,
            'prefix' => '',
            'foreign_key_constraints' => true,
            'busy_timeout' => 5000,
            'journal_mode' => 'wal',
        ],
    ]);

    DB::purge('concurrency');
    Artisan::call('migrate', ['--database' => 'concurrency', '--force' => true]);
    DB::setDefaultConnection('concurrency');
    DB::connection()->getPdo()->exec('PRAGMA busy_timeout = 5000');

    $tenant = makeTenant();
    inTenant($tenant, fn () => app(SeedTenantDefaultsAction::class)->execute());
    $branch = mainBranch($tenant);
    $department = inTenant($tenant, fn () => Department::query()->where('code', 'BIO')->firstOrFail());
    $receptionist = makeUser($tenant, RoleName::Receptionist);
    $patient = inTenant($tenant, fn () => Patient::factory()->create());
    $test = inTenant($tenant, fn () => app(SaveTestAction::class)->execute([
        'code' => 'CBC',
        'name' => 'CBC test',
        'department_id' => $department->id,
        'price' => '500',
        'tat_hours' => 4,
        'sample_type' => 'blood',
        'container' => 'edta',
    ]));

    DB::disconnect('concurrency');
    DB::setDefaultConnection('sqlite');

    $payload = [
        'patient_id' => $patient->id,
        'test_ids' => [$test->id],
    ];
    $failures = [];

    foreach ([1, 2] as $worker) {
        $pid = pcntl_fork();

        if ($pid === -1) {
            test()->fail('Could not fork');
        }

        if ($pid === 0) {
            try {
                DB::purge('concurrency');
                DB::setDefaultConnection('concurrency');

                for ($attempt = 1; $attempt <= 40; $attempt++) {
                    try {
                        DB::reconnect('concurrency');
                        inTenant($tenant, fn () => app(CreateOrderAction::class)->execute($branch, $payload, $receptionist));
                        break;
                    } catch (QueryException $e) {
                        DB::disconnect('concurrency');
                        if (! str_contains($e->getMessage(), 'database is locked') || $attempt === 40) {
                            throw $e;
                        }
                        usleep(random_int(20000, 120000));
                    }
                }

                file_put_contents($path.'.'.$worker.'.ok', '1');
            } catch (Throwable $e) {
                file_put_contents($path.'.'.$worker.'.log', $e::class.' '.$e->getMessage()."\n".$e->getTraceAsString());
            }

            posix_kill(getmypid(), SIGKILL);
        }
    }

    for ($i = 0; $i < 2; $i++) {
        pcntl_wait($status);
    }

    $logs = '';

    foreach ([1, 2] as $worker) {
        if (! is_file($path.'.'.$worker.'.ok')) {
            $failures[] = $worker;
            $logs .= (string) @file_get_contents($path.'.'.$worker.'.log');
        }
        @unlink($path.'.'.$worker.'.ok');
        @unlink($path.'.'.$worker.'.log');
    }

    DB::setDefaultConnection('concurrency');
    DB::reconnect('concurrency');
    $numbers = inTenant($tenant, fn () => Invoice::query()->pluck('invoice_no')->all());
    DB::disconnect('concurrency');
    DB::setDefaultConnection('sqlite');
    DB::purge('concurrency');
    @unlink($path);

    expect($failures)->toBe([], $logs)
        ->and($numbers)->toHaveCount(2)
        ->and(array_unique($numbers))->toHaveCount(2);
})->group('concurrency');
