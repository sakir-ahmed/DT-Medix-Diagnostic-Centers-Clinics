<?php

declare(strict_types=1);

namespace App\Actions\Hr;

use App\Models\AttendancePunch;
use App\Models\Employee;
use App\Services\SequenceGenerator;
use Illuminate\Support\Facades\DB;

final class SaveEmployeeAction
{
    public function __construct(private readonly SequenceGenerator $sequence) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Employee $employee = null): Employee
    {
        return DB::transaction(function () use ($data, $employee): Employee {
            $data['mobile'] = normalize_bd_mobile(isset($data['mobile']) && is_string($data['mobile']) ? $data['mobile'] : null);
            $employee ??= new Employee(['code' => sprintf('E-%04d', $this->sequence->next('employee'))]);
            $employee->fill($data)->save();

            if (filled($employee->device_pin)) {
                AttendancePunch::query()
                    ->where('branch_id', $employee->branch_id)
                    ->where('device_pin', $employee->device_pin)
                    ->whereNull('employee_id')
                    ->update(['employee_id' => $employee->id]);
            }

            return $employee;
        });
    }
}
