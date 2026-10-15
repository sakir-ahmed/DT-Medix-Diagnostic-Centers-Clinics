<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AccessLogController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\BranchSwitchController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\MessageTemplateController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReferrerController;
use App\Http\Controllers\Admin\ReportTemplateController;
use App\Http\Controllers\Admin\SearchController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\TestController;
use App\Http\Controllers\Admin\UiKitController;
use Illuminate\Support\Facades\Route;

/*
| Admin SPA routes. Loaded inside the "admin." name prefix with the auth,
| tenant, tenant.active and idle middleware.
*/

Route::get('/', DashboardController::class)->name('dashboard');
Route::post('/branch', BranchSwitchController::class)->name('branch.switch');

Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
Route::put('/profile/pin', [ProfileController::class, 'updatePin'])->name('profile.pin');
Route::post('/profile/two-factor', [ProfileController::class, 'enableTwoFactor'])->name('profile.two-factor.enable');
Route::post('/profile/two-factor/confirm', [ProfileController::class, 'confirmTwoFactor'])->name('profile.two-factor.confirm');
Route::delete('/profile/two-factor', [ProfileController::class, 'disableTwoFactor'])->name('profile.two-factor.disable');

Route::get('/ui-kit', [UiKitController::class, 'show'])->name('ui-kit');
Route::post('/ui-kit/validate', [UiKitController::class, 'validateDemo'])->name('ui-kit.validate');

Route::get('/search', SearchController::class)->name('search');

Route::middleware('can:branches.manage')->group(function (): void {
    Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
    Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
    Route::post('/branches/{branch}/toggle', [BranchController::class, 'toggle'])->name('branches.toggle');
});

Route::middleware('can:settings.manage')->group(function (): void {
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
});

Route::middleware('can:staff.manage')->group(function (): void {
    Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
    Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
});

Route::middleware('can:doctors.view')->group(function (): void {
    Route::get('/doctors', [DoctorController::class, 'index'])->name('doctors.index');
    Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])->name('doctors.show');
    Route::get('/doctors/{doctor}/signature', [DoctorController::class, 'signature'])->name('doctors.signature');
});

Route::middleware('can:doctors.manage')->group(function (): void {
    Route::post('/doctors', [DoctorController::class, 'store'])->name('doctors.store');
    Route::post('/doctors/{doctor}', [DoctorController::class, 'update'])->name('doctors.update');
});

Route::middleware('can:schedules.manage')->group(function (): void {
    Route::post('/doctors/{doctor}/schedules', [DoctorController::class, 'storeSchedule'])->name('doctors.schedules.store');
    Route::delete('/doctors/{doctor}/schedules/{schedule}', [DoctorController::class, 'destroySchedule'])->name('doctors.schedules.destroy');
    Route::post('/doctors/{doctor}/leaves', [DoctorController::class, 'storeLeave'])->name('doctors.leaves.store');
    Route::delete('/doctors/{doctor}/leaves/{leave}', [DoctorController::class, 'destroyLeave'])->name('doctors.leaves.destroy');
});

Route::middleware('can:patients.view')->group(function (): void {
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/lookup', [PatientController::class, 'lookup'])->name('patients.lookup');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
    Route::get('/patients/{patient}/photo', [PatientController::class, 'photo'])->name('patients.photo');
});

Route::post('/patients', [PatientController::class, 'store'])->middleware('can:patients.create')->name('patients.store');
Route::post('/patients/{patient}', [PatientController::class, 'update'])->middleware('can:patients.update')->name('patients.update');

Route::middleware('can:referrers.view')->group(function (): void {
    Route::get('/referrers', [ReferrerController::class, 'index'])->name('referrers.index');
    Route::get('/referrers/lookup', [ReferrerController::class, 'lookup'])->name('referrers.lookup');
});

Route::middleware('can:referrers.manage')->group(function (): void {
    Route::post('/referrers', [ReferrerController::class, 'store'])->name('referrers.store');
    Route::put('/referrers/{referrer}', [ReferrerController::class, 'update'])->name('referrers.update');
});

Route::get('/access-logs', [AccessLogController::class, 'index'])->middleware('can:access-log.view')->name('access-logs.index');

Route::middleware('can:catalog.view')->group(function (): void {
    Route::get('/tests', [TestController::class, 'index'])->name('tests.index');
    Route::get('/tests/lookup', [TestController::class, 'lookup'])->name('tests.lookup');
    Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
});

Route::middleware('can:catalog.manage')->group(function (): void {
    Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::get('/tests/create', [TestController::class, 'create'])->name('tests.create');
    Route::post('/tests', [TestController::class, 'store'])->name('tests.store');
    Route::get('/tests/{test}/edit', [TestController::class, 'edit'])->name('tests.edit');
    Route::put('/tests/{test}', [TestController::class, 'update'])->name('tests.update');
    Route::post('/packages', [PackageController::class, 'store'])->name('packages.store');
    Route::put('/packages/{package}', [PackageController::class, 'update'])->name('packages.update');
});

Route::middleware('can:templates.manage')->group(function (): void {
    Route::get('/report-templates', [ReportTemplateController::class, 'index'])->name('report-templates.index');
    Route::get('/report-templates/create', [ReportTemplateController::class, 'create'])->name('report-templates.create');
    Route::post('/report-templates', [ReportTemplateController::class, 'store'])->name('report-templates.store');
    Route::get('/report-templates/{reportTemplate}/edit', [ReportTemplateController::class, 'edit'])->name('report-templates.edit');
    Route::put('/report-templates/{reportTemplate}', [ReportTemplateController::class, 'update'])->name('report-templates.update');
});

Route::middleware('can:message-templates.manage')->group(function (): void {
    Route::get('/message-templates', [MessageTemplateController::class, 'index'])->name('message-templates.index');
    Route::put('/message-templates/{messageTemplate}', [MessageTemplateController::class, 'update'])->name('message-templates.update');
    Route::post('/message-templates/{messageTemplate}/reset', [MessageTemplateController::class, 'reset'])->name('message-templates.reset');
});
