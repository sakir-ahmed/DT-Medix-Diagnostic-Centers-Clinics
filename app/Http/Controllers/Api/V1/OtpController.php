<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OtpSendRequest;
use App\Http\Requests\Api\OtpVerifyRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;

/**
 * Staff sign-in by one-time code sent to the registered mobile.
 *
 * @tags Auth
 */
class OtpController extends Controller
{
    /**
     * Send a code. The response is the same whether or not the mobile exists.
     *
     * @unauthenticated
     */
    public function send(OtpSendRequest $request, OtpService $otp): JsonResponse
    {
        $mobile = (string) normalize_bd_mobile($request->string('mobile')->toString());

        if (User::query()->withoutGlobalScopes()->where('mobile', $mobile)->where('is_active', true)->exists()) {
            $otp->send($mobile, (string) $request->ip(), 'staff');
        }

        return ApiResponse::success(null, __('auth.otp_sent'));
    }

    /**
     * Verify the code and return a bearer token.
     *
     * @unauthenticated
     */
    public function verify(OtpVerifyRequest $request, OtpService $otp): JsonResponse
    {
        $mobile = (string) normalize_bd_mobile($request->string('mobile')->toString());
        /** @var User|null $user */
        $user = User::query()->withoutGlobalScopes()->where('mobile', $mobile)->where('is_active', true)->first();

        if ($user === null || ! $otp->verify($mobile, $request->string('code')->toString(), 'staff')) {
            return ApiResponse::error('OTP_INVALID', __('errors.OTP_INVALID'), 422);
        }

        $token = $user->createToken('otp', ['staff'])->plainTextToken;

        return ApiResponse::success(['token' => $token, 'token_type' => 'Bearer'], __('auth.logged_in'));
    }
}
