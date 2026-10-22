<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceDeviceRequest;
use App\Http\Requests\WorkShiftRequest;
use App\Models\AttendanceDevice;
use App\Models\Holiday;
use App\Models\WorkShift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shifts, ZKTeco devices and holidays on one setup page.
 */
class HrSetupController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Hr/Setup', [
            'shifts' => WorkShift::query()->orderBy('name')->get(),
            'devices' => AttendanceDevice::query()->orderBy('name')->get()->map(fn (AttendanceDevice $device): array => [
                'id' => $device->id,
                'name' => $device->name,
                'serial_number' => $device->serial_number,
                'location' => $device->location,
                'is_active' => $device->is_active,
                'last_seen_at' => $device->last_seen_at?->toIso8601String(),
            ]),
            'holidays' => Holiday::query()->whereDate('holiday_on', '>=', now()->startOfYear())->orderBy('holiday_on')->get()->map(fn (Holiday $holiday): array => [
                'id' => $holiday->id,
                'holiday_on' => $holiday->holiday_on->toDateString(),
                'name' => $holiday->name,
            ]),
            'pushUrl' => url('/iclock'),
        ]);
    }

    public function storeShift(WorkShiftRequest $request): RedirectResponse
    {
        WorkShift::query()->create($request->validated());

        return back()->with('success', __('hr.shift_saved'));
    }

    public function updateShift(WorkShiftRequest $request, WorkShift $shift): RedirectResponse
    {
        $shift->update($request->validated());

        return back()->with('success', __('hr.shift_saved'));
    }

    public function storeDevice(AttendanceDeviceRequest $request): RedirectResponse
    {
        AttendanceDevice::query()->create($request->validated());

        return back()->with('success', __('hr.device_saved'));
    }

    public function updateDevice(AttendanceDeviceRequest $request, AttendanceDevice $device): RedirectResponse
    {
        $device->update($request->validated());

        return back()->with('success', __('hr.device_saved'));
    }

    public function storeHoliday(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'holiday_on' => ['required', 'date', 'unique:holidays,holiday_on'],
            'name' => ['required', 'string', 'max:150'],
        ]);

        Holiday::query()->create($data);

        return back()->with('success', __('hr.holiday_saved'));
    }

    public function destroyHoliday(Holiday $holiday): RedirectResponse
    {
        $holiday->delete();

        return back()->with('success', __('hr.holiday_removed'));
    }
}
