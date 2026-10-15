<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Http\Middleware\ResolveTenant;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Checks staff credentials (username, email or mobile) with rate limiting.
 * On a tenant host, only that tenant's users may sign in.
 */
final class AuthenticateStaffAction
{
    public function execute(LoginRequest $request): User
    {
        $request->ensureIsNotRateLimited();

        $credentials = $request->getCredentials();
        $password = $credentials['password'];
        unset($credentials['password']);

        /** @var User|null $user */
        $user = User::query()->withoutGlobalScopes()->where($credentials)->whereNull('deleted_at')->first();
        $hostTenant = ResolveTenant::tenantFromHost($request->getHost());

        $valid = $user !== null
            && Hash::check($password, $user->password)
            && $user->is_active
            && ($hostTenant === null || $hostTenant->id === $user->tenant_id);

        if (! $valid) {
            $request->hitRateLimiter();

            throw ValidationException::withMessages(['login' => __('auth.failed')]);
        }

        $request->clearRateLimiter();

        return $user;
    }

    public function recordLogin(User $user): void
    {
        $user->forceFill(['last_login_at' => now()])->saveQuietly();
    }
}
