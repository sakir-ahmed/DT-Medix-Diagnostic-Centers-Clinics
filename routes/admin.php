<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\BranchSwitchController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
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
