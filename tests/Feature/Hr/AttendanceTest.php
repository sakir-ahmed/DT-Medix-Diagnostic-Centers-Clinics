<?php

declare(strict_types=1);

use App\Actions\Hr\CalculateAttendanceDayAction;
use App\Actions\Hr\RecordManualAttendanceAction;
use App\Enums\AttendanceStatus;
use App\Enums\RoleName;
use App\Models\AttendanceDevice;
use App\Models\AttendancePunch;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\WorkShift;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function (): void {
    makeCenter();
    $this->userId = makeUser(RoleName::Owner)->id;
    $this->travelTo('2026-10-22 20:00:00');
    $this->branch = mainBranch();
    $this->shift = WorkShift::factory()->create(['branch_id' => $this->branch->id, 'weekly_off' => [5]]);
    $this->employee = Employee::factory()->create(['branch_id' => $this->branch->id, 'device_pin' => '7', 'work_shift_id' => $this->shift->id]);
    $this->device = AttendanceDevice::factory()->create(['branch_id' => $this->branch->id, 'serial_number' => 'ZK001']);
});

function pushPunches(string $serial, string $body): TestResponse
{
    return test()->call('POST', '/iclock/cdata?SN='.$serial.'&table=ATTLOG&Stamp=9999', [], [], [], ['CONTENT_TYPE' => 'text/plain'], $body);
}

it('answers the device handshake and refuses unknown devices', function (): void {
    get('/iclock/cdata?SN=ZK001&options=all')->assertOk()->assertSee('GET OPTION FROM: ZK001');
    get('/iclock/getrequest?SN=ZK001')->assertOk()->assertSee('OK');
    get('/iclock/cdata?SN=NOPE')->assertNotFound();

    expect($this->device->fresh()->last_seen_at)->not->toBeNull();
});

it('stores pushed punches once and builds a late attendance day with overtime', function (): void {
    $body = "7\t2026-10-22 09:30:00\t0\t1\t0\t0\n7\t2026-10-22 19:00:00\t1\t1\t0\t0\n";

    pushPunches('ZK001', $body)->assertOk()->assertSee('OK: 2');
    pushPunches('ZK001', $body)->assertOk()->assertSee('OK: 0');

    expect(AttendancePunch::query()->count())->toBe(2);

    $record = AttendanceRecord::query()->where('employee_id', $this->employee->id)->firstOrFail();
    expect($record->status)->toBe(AttendanceStatus::Late)
        ->and($record->late_minutes)->toBe(30)
        ->and($record->overtime_minutes)->toBe(60)
        ->and($record->worked_minutes)->toBe(570)
        ->and($record->source)->toBe('device');
});

it('keeps punches of unknown PINs and links them when the employee is saved', function (): void {
    pushPunches('ZK001', "99\t2026-10-22 09:00:00\t0\t1\t0\t0\n")->assertOk();

    expect(AttendancePunch::query()->whereNull('employee_id')->count())->toBe(1);

    $stranger = Employee::factory()->create(['branch_id' => $this->branch->id, 'device_pin' => null]);
    $this->actingAs(makeUser(RoleName::Owner))->put(route('admin.employees.update', $stranger), [
        'name' => $stranger->name, 'joined_on' => '2026-01-01', 'device_pin' => '99', 'is_active' => true,
    ])->assertSessionHasNoErrors();

    expect(AttendancePunch::query()->where('employee_id', $stranger->id)->count())->toBe(1);
});

it('marks a day without punches as absent, weekly off, holiday or leave', function (): void {
    $calculate = app(CalculateAttendanceDayAction::class);
    $employee = $this->employee->load('workShift');

    // Wednesday: absent. Friday: weekly off.
    expect($calculate->execute($employee, CarbonImmutable::parse('2026-10-21'))->status)->toBe(AttendanceStatus::Absent)
        ->and($calculate->execute($employee, CarbonImmutable::parse('2026-10-16'))->status)->toBe(AttendanceStatus::WeeklyOff);

    Holiday::factory()->create(['holiday_on' => '2026-10-20', 'name' => 'Test holiday']);
    expect($calculate->execute($employee, CarbonImmutable::parse('2026-10-20'))->status)->toBe(AttendanceStatus::Holiday);

    $manager = makeUser(RoleName::BranchManager);
    $this->actingAs($manager)->post(route('admin.leaves.store'), ['employee_id' => $employee->id, 'type' => 'sick', 'from_date' => '2026-10-19', 'to_date' => '2026-10-19'])->assertSessionHasNoErrors();
    $leave = LeaveRequest::query()->firstOrFail();
    $this->actingAs($manager)->post(route('admin.leaves.approve', $leave))->assertSessionHasNoErrors();

    expect(AttendanceRecord::query()->whereDate('work_date', '2026-10-19')->firstOrFail()->status)->toBe(AttendanceStatus::Leave);
});

it('never overwrites a manual entry with device data and can revert it', function (): void {
    $manager = makeUser(RoleName::BranchManager);

    $this->actingAs($manager)->post(route('admin.attendance.store'), [
        'employee_id' => $this->employee->id, 'work_date' => '2026-10-22', 'status' => 'present', 'check_in' => '09:00', 'check_out' => '18:00', 'note' => 'Device offline',
    ])->assertSessionHasNoErrors();

    pushPunches('ZK001', "7\t2026-10-22 11:00:00\t0\t1\t0\t0\n7\t2026-10-22 12:00:00\t1\t1\t0\t0\n")->assertOk();

    $record = AttendanceRecord::query()->where('employee_id', $this->employee->id)->firstOrFail();
    expect($record->is_manual)->toBeTrue()
        ->and($record->check_in)->toBe('09:00:00')
        ->and($record->status)->toBe(AttendanceStatus::Present);

    $this->actingAs($manager)->delete(route('admin.attendance.destroy', $record))->assertSessionHasNoErrors();

    $rebuilt = AttendanceRecord::query()->where('employee_id', $this->employee->id)->firstOrFail();
    expect($rebuilt->is_manual)->toBeFalse()->and($rebuilt->check_in)->toBe('11:00:00');
});

it('imports a ZKTeco export file and skips bad lines', function (): void {
    $file = UploadedFile::fake()->createWithContent('att.dat', "7\t2026-10-21 09:00:00\t0\t1\n7\t2026-10-21 18:30:00\t1\t1\nbad line\n");

    $this->actingAs(makeUser(RoleName::BranchManager))->post(route('admin.attendance.import'), ['file' => $file])->assertSessionHasNoErrors();

    expect(AttendancePunch::query()->where('source', 'import')->count())->toBe(2)
        ->and(AttendanceRecord::query()->whereDate('work_date', '2026-10-21')->firstOrFail()->status)->toBe(AttendanceStatus::Present);
});

it('shows the daily and monthly attendance pages', function (): void {
    app(RecordManualAttendanceAction::class)->execute($this->employee, ['work_date' => '2026-10-22', 'status' => 'present', 'check_in' => '09:00', 'check_out' => '18:00'], (int) test()->userId);

    $this->actingAs(makeUser(RoleName::BranchManager))->get(route('admin.attendance.index', ['date' => '2026-10-22']))
        ->assertInertia(fn ($page) => $page->component('Admin/Hr/Attendance')->where('counts.present', 1)->has('rows', 1));

    $this->actingAs(makeUser(RoleName::BranchManager))->get(route('admin.attendance.index', ['view' => 'month', 'month' => '2026-10']))
        ->assertInertia(fn ($page) => $page->where('daysInMonth', 31)->where('rows.0.present', 1));
});

it('validates manual attendance input', function (): void {
    post(route('admin.attendance.store'))->assertRedirect(route('login'));

    $this->actingAs(makeUser(RoleName::BranchManager))->post(route('admin.attendance.store'), [
        'employee_id' => $this->employee->id, 'work_date' => '2026-10-22', 'status' => 'present', 'check_in' => '18:00', 'check_out' => '09:00',
    ])->assertSessionHasErrors('check_out');
});
