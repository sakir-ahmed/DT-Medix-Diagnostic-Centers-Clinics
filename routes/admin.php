<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AccessLogController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\BranchSwitchController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\DoctorQueueController;
use App\Http\Controllers\Admin\LabController;
use App\Http\Controllers\Admin\MessageTemplateController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\PrintController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReceptionController;
use App\Http\Controllers\Admin\ReferrerController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\ReportTemplateController;
use App\Http\Controllers\Admin\SampleController;
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
    Route::get('/patients/{patient}/summary', [PatientController::class, 'summary'])->name('patients.summary');
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

Route::middleware('can:orders.create')->group(function (): void {
    Route::get('/reception', [ReceptionController::class, 'index'])->name('reception.index');
    Route::post('/reception', [ReceptionController::class, 'store'])->name('reception.store');
});

Route::middleware('can:appointments.view')->group(function (): void {
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
});

Route::middleware('can:appointments.manage')->group(function (): void {
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
});

Route::middleware('can:queue.view')->group(function (): void {
    Route::get('/my-queue', [DoctorQueueController::class, 'index'])->name('doctor.queue');
    Route::post('/my-queue/next', [DoctorQueueController::class, 'next'])->name('doctor.queue.next');
});

Route::middleware('can:orders.view')->group(function (): void {
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/print', [PrintController::class, 'show'])->name('orders.print');
});

Route::post('/orders/{order}/payments', [OrderController::class, 'pay'])->middleware('can:payments.collect')->name('orders.payments.store');
Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->middleware('can:orders.cancel')->name('orders.cancel');

Route::middleware('can:discounts.approve')->group(function (): void {
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::post('/approvals/{discountRequest}', [ApprovalController::class, 'decide'])->name('approvals.decide');
});

Route::post('/refunds', [RefundController::class, 'store'])->middleware('can:refunds.request')->name('refunds.store');

Route::middleware('can:refunds.approve')->group(function (): void {
    Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
    Route::post('/refunds/{refund}/decide', [RefundController::class, 'decide'])->name('refunds.decide');
});

Route::middleware('can:samples.collect')->group(function (): void {
    Route::get('/samples/collection', [SampleController::class, 'collection'])->name('samples.collection');
    Route::post('/samples/{sample}/collect', [SampleController::class, 'collect'])->name('samples.collect');
    Route::get('/samples/{sample}/label', [PrintController::class, 'label'])->name('samples.label');
});

Route::middleware('can:samples.receive')->group(function (): void {
    Route::get('/samples/receive', [SampleController::class, 'receive'])->name('samples.receive');
    Route::post('/samples/receive', [SampleController::class, 'scan'])->name('samples.scan');
});

Route::post('/samples/{sample}/reject', [SampleController::class, 'reject'])->middleware('can:samples.reject')->name('samples.reject');

Route::middleware('can:lab.worklist')->group(function (): void {
    Route::get('/lab/worklist', [LabController::class, 'worklist'])->name('lab.worklist');
    Route::get('/lab/samples/{sample}', [LabController::class, 'show'])->name('lab.sample');
});

Route::get('/lab/critical', [LabController::class, 'critical'])->middleware('can:critical.view')->name('lab.critical');

Route::middleware('can:results.enter')->group(function (): void {
    Route::get('/lab/items/{item}', [LabController::class, 'results'])->name('lab.results');
    Route::put('/lab/items/{item}', [LabController::class, 'save'])->name('lab.results.update');
    Route::post('/lab/items/{item}/submit', [LabController::class, 'submit'])->name('lab.results.submit');
    Route::post('/lab/items/{item}/acknowledge', [LabController::class, 'acknowledge'])->name('lab.results.acknowledge');
    Route::post('/lab/items/{item}/attachments', [LabController::class, 'attach'])->name('lab.results.attachments');
    Route::delete('/lab/attachments/{attachment}', [LabController::class, 'destroyAttachment'])->name('lab.attachments.destroy');
    Route::get('/lab/attachments/{attachment}/download', [LabController::class, 'download'])->name('lab.attachments.download');
});

Route::middleware('can:message-templates.manage')->group(function (): void {
    Route::get('/message-templates', [MessageTemplateController::class, 'index'])->name('message-templates.index');
    Route::put('/message-templates/{messageTemplate}', [MessageTemplateController::class, 'update'])->name('message-templates.update');
    Route::post('/message-templates/{messageTemplate}/reset', [MessageTemplateController::class, 'reset'])->name('message-templates.reset');
});
