<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OtpController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login');
        Route::post('/otp/send', [OtpController::class, 'send'])->name('otp.send');
        Route::post('/otp/verify', [OtpController::class, 'verify'])->middleware('throttle:login')->name('otp.verify');

        Route::middleware(['auth:sanctum', 'abilities:staff', 'tenant'])->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/me', [AuthController::class, 'me'])->name('me');
        });
    });

    Route::middleware(['auth:sanctum', 'abilities:staff', 'tenant', 'tenant.active', 'throttle:api'])
        ->group(base_path('routes/api_v1.php'));
});
