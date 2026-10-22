<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AttendanceDevice;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceDevice>
 */
class AttendanceDeviceFactory extends Factory
{
    protected $model = AttendanceDevice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => 'Main gate',
            'serial_number' => strtoupper(fake()->unique()->bothify('ZK######??')),
            'location' => 'Reception',
            'is_active' => true,
        ];
    }
}
