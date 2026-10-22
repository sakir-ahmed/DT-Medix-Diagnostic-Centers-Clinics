<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'code' => 'E-'.fake()->unique()->numerify('####'),
            'name' => fake()->name(),
            'designation' => 'Lab assistant',
            'department' => 'Laboratory',
            'mobile' => '88017'.fake()->numerify('########'),
            'joined_on' => now()->subYear()->toDateString(),
            'device_pin' => (string) fake()->unique()->numberBetween(1, 9999),
            'basic_salary' => '15000.00',
            'house_allowance' => '3000.00',
            'medical_allowance' => '1000.00',
            'transport_allowance' => '1000.00',
            'other_allowance' => '0.00',
            'overtime_rate' => '100.00',
            'is_active' => true,
        ];
    }
}
