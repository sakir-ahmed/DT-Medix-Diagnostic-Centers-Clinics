<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'BR'.fake()->unique()->numberBetween(10, 99),
            'name' => fake()->city().' Branch',
            'address' => fake()->address(),
            'phone' => '0171'.fake()->numerify('#######'),
            'is_active' => true,
        ];
    }
}
