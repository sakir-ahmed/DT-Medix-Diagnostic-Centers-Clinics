<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\DoctorController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\LabController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\ReferrerController;
use App\Http\Controllers\Api\V1\RefundController;
use App\Http\Controllers\Api\V1\SampleController;
use Illuminate\Support\Facades\Route;

/*
| Staff API v1. Loaded inside the "api.v1." name prefix with the
| auth:sanctum, abilities:staff, tenant, tenant.active and throttle:api
| middleware. Branch operations are prefixed /branches/{branch}.
*/

Route::get('/patients', [PatientController::class, 'index'])->middleware('can:patients.view')->name('patients.index');
Route::post('/patients', [PatientController::class, 'store'])->middleware('can:patients.create')->name('patients.store');
Route::get('/patients/{patient}', [PatientController::class, 'show'])->middleware('can:patients.view')->name('patients.show');
Route::patch('/patients/{patient}', [PatientController::class, 'update'])->middleware('can:patients.update')->name('patients.update');

Route::get('/doctors', [DoctorController::class, 'index'])->middleware('can:doctors.view')->name('doctors.index');

Route::get('/referrers', [ReferrerController::class, 'index'])->middleware('can:referrers.view')->name('referrers.index');
Route::post('/referrers', [ReferrerController::class, 'store'])->middleware('can:referrers.manage')->name('referrers.store');
Route::put('/referrers/{referrer}', [ReferrerController::class, 'update'])->middleware('can:referrers.manage')->name('referrers.update');

Route::get('/tests', [CatalogController::class, 'tests'])->middleware('can:catalog.view')->name('tests.index');
Route::post('/tests', [CatalogController::class, 'storeTest'])->middleware('can:catalog.manage')->name('tests.store');
Route::put('/tests/{test}', [CatalogController::class, 'updateTest'])->middleware('can:catalog.manage')->name('tests.update');
Route::get('/tests/{test}/parameters', [CatalogController::class, 'parameters'])->middleware('can:catalog.view')->name('tests.parameters');
Route::get('/packages', [CatalogController::class, 'packages'])->middleware('can:catalog.view')->name('packages.index');
Route::post('/packages', [CatalogController::class, 'storePackage'])->middleware('can:catalog.manage')->name('packages.store');
Route::put('/packages/{package}', [CatalogController::class, 'updatePackage'])->middleware('can:catalog.manage')->name('packages.update');
Route::get('/report-templates', [CatalogController::class, 'reportTemplates'])->middleware('can:templates.manage')->name('report-templates.index');
Route::post('/report-templates', [CatalogController::class, 'storeReportTemplate'])->middleware('can:templates.manage')->name('report-templates.store');
Route::put('/report-templates/{reportTemplate}', [CatalogController::class, 'updateReportTemplate'])->middleware('can:templates.manage')->name('report-templates.update');

Route::prefix('branches/{branch}')->middleware('branch')->group(function (): void {
    Route::get('/doctors/{doctor}/slots', [AppointmentController::class, 'slots'])->middleware('can:appointments.view')->name('doctors.slots');
    Route::post('/appointments', [AppointmentController::class, 'store'])->middleware(['can:appointments.manage', 'idempotent'])->name('appointments.store');
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->middleware('can:appointments.manage')->name('appointments.status');
    Route::get('/doctor/queue', [AppointmentController::class, 'queue'])->middleware('can:queue.view')->name('doctor.queue');

    Route::post('/orders', [OrderController::class, 'store'])->middleware(['can:orders.create', 'idempotent'])->name('orders.store');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->middleware('can:orders.view')->name('orders.show');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->middleware('can:orders.cancel')->name('orders.cancel');
    Route::post('/orders/{order}/discount-requests', [OrderController::class, 'storeDiscount'])->middleware('can:invoices.discount')->name('orders.discount-requests.store');
    Route::post('/discount-requests/{discountRequest}/approve', [OrderController::class, 'decideDiscount'])->middleware('can:discounts.approve')->name('discount-requests.approve');

    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'pay'])->middleware(['can:payments.collect', 'idempotent'])->name('invoices.payments.store');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->middleware('can:orders.view')->name('invoices.print');

    Route::post('/refunds', [RefundController::class, 'store'])->middleware('can:refunds.request')->name('refunds.store');
    Route::post('/refunds/{refund}/approve', [RefundController::class, 'decide'])->middleware('can:refunds.approve')->name('refunds.approve');

    Route::prefix('samples')->group(function (): void {
        Route::post('/{sample}/collect', [SampleController::class, 'collect'])->middleware('can:samples.collect')->name('samples.collect');
        Route::post('/{sample}/receive', [SampleController::class, 'receive'])->middleware('can:samples.receive')->name('samples.receive');
        Route::post('/{sample}/reject', [SampleController::class, 'reject'])->middleware('can:samples.reject')->name('samples.reject');
        Route::get('/{sample}/label', [SampleController::class, 'label'])->middleware('can:samples.collect')->name('samples.label');
    });

    Route::get('/lab/worklist', [LabController::class, 'worklist'])->middleware('can:lab.worklist')->name('lab.worklist');
    Route::get('/lab/results/{item}', [LabController::class, 'showResults'])->middleware('can:results.enter')->name('lab.results.show');
    Route::put('/lab/results/{item}', [LabController::class, 'updateResults'])->middleware('can:results.enter')->name('lab.results.update');
    Route::post('/lab/results/{item}/acknowledge-critical', [LabController::class, 'acknowledgeCritical'])->middleware('can:results.enter')->name('lab.results.acknowledge');
    Route::post('/lab/results/{item}/submit', [LabController::class, 'submit'])->middleware('can:results.enter')->name('lab.results.submit');
});
