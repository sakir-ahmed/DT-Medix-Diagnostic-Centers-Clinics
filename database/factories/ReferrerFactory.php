<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReferrerType;
use App\Models\Referrer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Referrer>
 */
class ReferrerFactory extends Factory
{
    protected $model = Referrer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => ReferrerType::Doctor,
            'code' => 'R'.fake()->unique()->numerify('###'),
            'name' => 'Dr. '.fake()->name(),
            'organization' => fake()->company().' Hospital',
            'mobile' => '88018'.fake()->numerify('########'),
            'is_active' => true,
        ];
    }
}
