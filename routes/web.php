<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\DemoLoginController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/doctors', [SiteController::class, 'doctors'])->name('public.doctors');
Route::get('/tests', [SiteController::class, 'tests'])->name('public.tests');
Route::get('/booking', [BookingController::class, 'create'])->name('public.booking');
Route::get('/booking/slots', [BookingController::class, 'slots'])->middleware('throttle:60,1')->name('public.booking.slots');
Route::post('/booking', [BookingController::class, 'store'])->middleware('throttle:10,1')->name('public.booking.store');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->middleware('throttle:login')->name('two-factor.store');
    Route::post('/demo-login/{user}', [DemoLoginController::class, 'store'])->middleware(['throttle:login', 'demo.mode'])->name('demo.login');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'branch.scope', 'idle'])->group(base_path('routes/admin.php'));
