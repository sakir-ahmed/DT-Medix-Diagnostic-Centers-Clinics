<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\WorkShift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkShift>
 */
class WorkShiftFactory extends Factory
{
    protected $model = WorkShift::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => 'Day shift',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'grace_minutes' => 10,
            'weekly_off' => [5],
            'is_active' => true,
        ];
    }
}
