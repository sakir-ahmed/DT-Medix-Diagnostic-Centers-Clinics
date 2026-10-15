<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\AuthenticateStaffAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\MeResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @tags Auth
 */
class AuthController extends Controller
{
    /**
     * Staff login with username, email or mobile. Returns a bearer token.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request, AuthenticateStaffAction $authenticate, TenantContext $context): JsonResponse
    {
        $user = $authenticate->execute($request);
        $authenticate->recordLogin($user);

        $token = $user->createToken((string) ($request->input('device_name') ?: 'api'), ['staff'])->plainTextToken;

        $context->set($user->tenant);
        $context->restrictBranches($user->accessibleBranchIds());

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => (new MeResource($user))->resolve(),
        ], __('auth.logged_in'));
    }

    /**
     * Revoke the current token.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return ApiResponse::success(null, __('auth.logged_out'));
    }

    /**
     * The signed-in user with roles, permissions, branches, plan and modules.
     */
    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new MeResource($request->user()));
    }
}
