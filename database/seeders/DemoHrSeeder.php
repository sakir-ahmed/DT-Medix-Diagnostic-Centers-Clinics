<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Hr\FinalizePayrollRunAction;
use App\Actions\Hr\GeneratePayrollRunAction;
use App\Actions\Hr\IngestAttendancePunchesAction;
use App\Actions\Hr\RecordManualAttendanceAction;
use App\Actions\Hr\RequestLeaveAction;
use App\Actions\Hr\SaveEmployeeAction;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceDevice;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\User;
use App\Models\WorkShift;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Demo staff, a ZKTeco device, ten weeks of punches and two payroll runs.
 */
class DemoHrSeeder extends Seeder
{
    public function run(
        SaveEmployeeAction $save,
        IngestAttendancePunchesAction $ingest,
        RecordManualAttendanceAction $manual,
        RequestLeaveAction $leave,
        GeneratePayrollRunAction $payroll,
        FinalizePayrollRunAction $finalize,
    ): void {
        $branch = Branch::query()->withoutGlobalScopes()->orderBy('id')->first();

        if ($branch === null || Employee::query()->withoutGlobalScopes()->exists()) {
            return;
        }

        $shift = WorkShift::query()->create(['branch_id' => $branch->id, 'name' => 'Day shift', 'start_time' => '09:00', 'end_time' => '18:00', 'grace_minutes' => 10, 'weekly_off' => [5]]);
        AttendanceDevice::query()->create(['branch_id' => $branch->id, 'name' => 'Main entrance', 'serial_number' => 'ZKDEMO0001', 'location' => 'Reception']);
        Holiday::query()->firstOrCreate(['holiday_on' => now()->addDays(10)->toDateString()], ['name' => 'Public holiday']);

        $people = [
            ['Nasima Akter', 'Receptionist', 'Front desk', 22000], ['Kamrul Hasan', 'Lab technician', 'Laboratory', 26000],
            ['Shirin Sultana', 'Lab technician', 'Laboratory', 25000], ['Rafiq Mia', 'Sample collector', 'Collection', 18000],
            ['Tania Rahman', 'Nurse', 'Clinic', 24000], ['Jahid Hossain', 'Accounts officer', 'Accounts', 30000],
            ['Moni Begum', 'Cleaner', 'Support', 12000], ['Sohel Rana', 'Security guard', 'Support', 13000],
        ];

        $employees = [];
        foreach ($people as $index => [$name, $designation, $department, $basic]) {
            $employees[] = $save->execute([
                'branch_id' => $branch->id,
                'name' => $name,
                'designation' => $designation,
                'department' => $department,
                'mobile' => sprintf('880171%07d', 2000 + $index),
                'joined_on' => now()->subMonths(8)->toDateString(),
                'device_pin' => (string) ($index + 1),
                'work_shift_id' => $shift->id,
                'basic_salary' => number_format($basic * 0.6, 2, '.', ''),
                'house_allowance' => number_format($basic * 0.25, 2, '.', ''),
                'medical_allowance' => number_format($basic * 0.1, 2, '.', ''),
                'transport_allowance' => number_format($basic * 0.05, 2, '.', ''),
                'other_allowance' => '0.00',
                'overtime_rate' => '80.00',
                'payment_method' => $index % 2 === 0 ? 'bkash' : 'cash',
                'is_active' => true,
            ]);
        }

        $rows = [];
        $today = CarbonImmutable::today();
        mt_srand(11);

        for ($offset = 75; $offset >= 0; $offset--) {
            $day = $today->subDays($offset);

            if ($day->dayOfWeek === 5) {
                continue;
            }

            foreach ($employees as $employee) {
                if (mt_rand(1, 100) <= 6 && $offset > 0) {
                    continue;
                }

                $in = $day->setTime(8, 45)->addMinutes(mt_rand(0, 45));
                $out = $day->setTime(17, 55)->addMinutes(mt_rand(0, 70));

                if ($day->isToday() && now()->lessThan($out)) {
                    $out = null;
                }

                $rows[] = [(string) $employee->device_pin, $in->format('Y-m-d H:i:s')];

                if ($out !== null) {
                    $rows[] = [(string) $employee->device_pin, $out->format('Y-m-d H:i:s')];
                }
            }
        }

        $ingest->execute($branch->id, $rows, 'device', AttendanceDevice::query()->withoutGlobalScopes()->first());

        $admin = User::query()->orderBy('id')->first();
        $manual->execute($employees[0], ['work_date' => $today->subDays(3)->toDateString(), 'status' => AttendanceStatus::Present->value, 'check_in' => '09:00', 'check_out' => '18:00', 'note' => 'Device was offline'], (int) $admin?->id);
        $leave->execute($employees[4], ['type' => 'sick', 'from_date' => $today->addDays(2)->toDateString(), 'to_date' => $today->addDays(3)->toDateString(), 'reason' => 'Medical appointment']);

        $previous = $payroll->execute($branch->id, $today->subMonthNoOverflow()->format('Y-m'));
        $finalize->execute($previous, (int) $admin?->id);
        $payroll->execute($branch->id, $today->format('Y-m'));
    }
}
