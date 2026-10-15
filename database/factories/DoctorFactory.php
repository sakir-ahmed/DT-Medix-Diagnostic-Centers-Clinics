<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Doctor>
 */
class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Dr. '.fake()->name(),
            'specialty' => fake()->randomElement(['Medicine', 'Gynae and Obs', 'Pediatrics', 'Cardiology', 'Orthopedics']),
            'qualifications' => 'MBBS, FCPS',
            'bmdc_no' => 'A-'.fake()->unique()->numerify('#####'),
            'consultation_fee' => '800.00',
            'follow_up_fee' => '400.00',
            'slot_minutes' => 15,
            'is_active' => true,
        ];
    }
}
