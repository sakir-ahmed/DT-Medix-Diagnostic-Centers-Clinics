<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Plan;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Diagnostic';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'plan' => Plan::Pro,
            'status' => TenantStatus::Active,
            'trial_ends_at' => now()->addDays(30),
            'limits' => Plan::Pro->limits(),
            'modules' => Plan::Pro->modules(),
            'settings' => [],
            'message_credits' => 1000,
        ];
    }
}
