<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Platform\ImpersonationController;
use App\Http\Controllers\Platform\TenantController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->middleware('throttle:login')->name('two-factor.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::post('/impersonation/leave', [ImpersonationController::class, 'destroy'])->name('impersonation.leave');
});

Route::prefix('platform')->name('platform.')->middleware(['auth', 'super-admin', 'idle'])->group(function (): void {
    Route::get('/', fn () => redirect()->route('platform.tenants.index'));
    Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
    Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenants.create');
    Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
    Route::get('/tenants/{tenant}/edit', [TenantController::class, 'edit'])->name('tenants.edit');
    Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
    Route::post('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
    Route::post('/tenants/{tenant}/resume', [TenantController::class, 'resume'])->name('tenants.resume');
    Route::post('/tenants/{tenant}/export', [TenantController::class, 'export'])->name('tenants.export');
    Route::post('/tenants/{tenant}/impersonate', [ImpersonationController::class, 'store'])->name('tenants.impersonate');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'tenant', 'tenant.active', 'idle'])->group(base_path('routes/admin.php'));
