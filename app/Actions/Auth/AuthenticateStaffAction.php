<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Checks staff credentials (username, email or mobile) with rate limiting.
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
        $user = User::query()->where($credentials)->first();

        $valid = $user !== null
            && Hash::check($password, $user->password)
            && $user->is_active;

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
