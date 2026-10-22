<?php

declare(strict_types=1);

namespace App\Actions\Hr;

use App\Enums\LeaveStatus;
use App\Exceptions\DomainException;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\CarbonImmutable;

final class RequestLeaveAction
{
    /**
     * @param  array{type: string, from_date: string, to_date: string, reason?: string|null}  $data
     */
    public function execute(Employee $employee, array $data): LeaveRequest
    {
        $from = CarbonImmutable::parse($data['from_date'])->startOfDay();
        $to = CarbonImmutable::parse($data['to_date'])->startOfDay();

        $overlaps = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->whereIn('status', [LeaveStatus::Pending, LeaveStatus::Approved])
            ->whereDate('from_date', '<=', $to)
            ->whereDate('to_date', '>=', $from)
            ->exists();

        if ($overlaps) {
            throw DomainException::make('LEAVE_OVERLAP');
        }

        return LeaveRequest::query()->create([
            'branch_id' => $employee->branch_id,
            'employee_id' => $employee->id,
            'type' => $data['type'],
            'from_date' => $from,
            'to_date' => $to,
            'days' => (int) $from->diffInDays($to) + 1,
            'reason' => $data['reason'] ?? null,
            'status' => LeaveStatus::Pending,
        ]);
    }
}
