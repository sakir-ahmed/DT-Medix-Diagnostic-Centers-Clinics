<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Sex;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_code' => 'P-'.now()->format('y').'-'.fake()->unique()->numerify('######'),
            'name' => fake()->name(),
            'sex' => fake()->randomElement([Sex::Male, Sex::Female]),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-1 year')->format('Y-m-d'),
            'mobile' => '88017'.fake()->numerify('########'),
            'preferred_locale' => 'en',
        ];
    }
}
