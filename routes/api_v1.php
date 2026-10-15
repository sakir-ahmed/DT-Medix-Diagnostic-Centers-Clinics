<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\DoctorController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\ReferrerController;
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
