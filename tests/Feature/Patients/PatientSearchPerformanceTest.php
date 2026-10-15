<?php

declare(strict_types=1);

use App\Models\Patient;
use Illuminate\Support\Facades\DB;

/*
| Acceptance: patient search returns in under 300 ms on 500,000 patients.
| Seeding takes a while, so the test runs in the "performance" group:
|   php artisan test --group=performance
*/
it('searches 500,000 patients by mobile, name and ID in under 300 ms', function (): void {
    $tenant = makeTenant();
    $now = now()->toDateTimeString();
    $names = ['Rahim', 'Karim', 'Fatema', 'Ayesha', 'Hasan', 'Nusrat', 'Sabbir', 'Mitu', 'Jamal', 'Rupa'];
    $rows = [];

    DB::disableQueryLog();
    for ($i = 1; $i <= 500000; $i++) {
        $rows[] = [
            'tenant_id' => $tenant->id,
            'patient_code' => sprintf('P-26-%06d', $i),
            'name' => $names[$i % 10].' '.$names[($i * 7) % 10].' '.$i,
            'sex' => $i % 2 === 0 ? 'male' : 'female',
            'mobile' => sprintf('88017%08d', $i),
            'preferred_locale' => 'en',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (count($rows) === 2000) {
            DB::table('patients')->insert($rows);
            $rows = [];
        }
    }

    expect(DB::table('patients')->count())->toBe(500000);

    foreach (['01700250000', 'Fatema Has', 'P-26-499999'] as $term) {
        $start = hrtime(true);
        $found = inTenant($tenant, fn () => Patient::query()->search($term)->orderByDesc('id')->limit(10)->get());
        $elapsedMs = (hrtime(true) - $start) / 1e6;

        expect($found)->not->toBeEmpty()
            ->and($elapsedMs)->toBeLessThan(300.0);
    }
})->group('performance');
