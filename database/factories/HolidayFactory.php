<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Holiday>
 */
class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'holiday_on' => fake()->unique()->dateTimeBetween('+1 month', '+1 year')->format('Y-m-d'),
            'name' => fake()->words(2, true),
        ];
    }
}
