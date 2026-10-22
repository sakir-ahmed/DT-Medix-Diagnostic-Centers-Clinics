<?php

declare(strict_types=1);

namespace App\Actions\Hr;

use App\Models\AttendanceDevice;
use App\Models\AttendancePunch;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Stores raw punches (from a device push or a file import), skips ones that
 * were already received, then recalculates the affected employee-days.
 */
final class IngestAttendancePunchesAction
{
    public function __construct(private readonly CalculateAttendanceDayAction $day) {}

    /**
     * @param  list<array{0: string, 1: string}>  $rows  [device PIN, "Y-m-d H:i:s"]
     * @return array{created: int, duplicates: int, skipped: int}
     */
    public function execute(int $branchId, array $rows, string $source, ?AttendanceDevice $device = null): array
    {
        $result = ['created' => 0, 'duplicates' => 0, 'skipped' => 0];
        $affected = [];
        $employees = Employee::query()->withoutGlobalScopes()->where('branch_id', $branchId)->whereNotNull('device_pin')->get()->keyBy('device_pin');

        DB::transaction(function () use ($branchId, $rows, $source, $device, $employees, &$result, &$affected): void {
            foreach ($rows as [$pin, $time]) {
                $pin = trim($pin);

                try {
                    $at = Date::parse(trim($time), config('app.timezone'));
                } catch (Throwable) {
                    $result['skipped']++;

                    continue;
                }

                if ($pin === '' || $at->isFuture()) {
                    $result['skipped']++;

                    continue;
                }

                $punch = AttendancePunch::query()->withoutGlobalScopes()->firstOrCreate(
                    ['branch_id' => $branchId, 'device_pin' => $pin, 'punched_at' => $at],
                    ['employee_id' => $employees[$pin]->id ?? null, 'attendance_device_id' => $device?->id, 'source' => $source],
                );

                if (! $punch->wasRecentlyCreated) {
                    $result['duplicates']++;

                    continue;
                }

                $result['created']++;

                if (isset($employees[$pin])) {
                    $affected[$employees[$pin]->id.'|'.$at->toDateString()] = [$employees[$pin], CarbonImmutable::parse($at)->startOfDay()];
                }
            }
        });

        foreach ($affected as [$employee, $date]) {
            $employee->loadMissing('workShift');
            $this->day->execute($employee, $date);
        }

        return $result;
    }
}
