<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateStaffAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('login.id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function store(Request $request, Google2FA $google2fa, AuthenticateStaffAction $authenticate): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $userId = $request->session()->get('login.id');
        /** @var User|null $user */
        $user = $userId === null ? null : User::query()->withoutGlobalScopes()->find($userId);

        if ($user === null || ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('login');
        }

        $key = 'two-factor:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['code' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)])]);
        }

        if (! $google2fa->verifyKey((string) $user->two_factor_secret, (string) $request->input('code'))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['code' => __('auth.two_factor_invalid')]);
        }

        RateLimiter::clear($key);
        $remember = (bool) $request->session()->pull('login.remember', false);
        $request->session()->forget('login.id');

        return LoginController::completeLogin($request, $user, $remember, $authenticate);
    }
}
