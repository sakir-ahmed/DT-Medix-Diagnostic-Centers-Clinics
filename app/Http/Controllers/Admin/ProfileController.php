<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class ProfileController extends Controller
{
    public function edit(Request $request, Google2FA $google2fa): Response
    {
        /** @var User $user */
        $user = $request->user();
        $setup = null;

        if ($user->two_factor_secret !== null && $user->two_factor_confirmed_at === null) {
            $url = $google2fa->getQRCodeUrl((string) config('app.name'), (string) ($user->email ?? $user->username ?? $user->id), $user->two_factor_secret);
            $qr = (new Builder(writer: new SvgWriter, data: $url, size: 180, margin: 4))->build();
            $setup = ['qr' => $qr->getDataUri(), 'secret' => $user->two_factor_secret];
        }

        return Inertia::render('Admin/Profile', [
            'twoFactorEnabled' => $user->hasTwoFactorEnabled(),
            'twoFactorSetup' => $setup,
            'hasApprovalPin' => $user->approval_pin !== null,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->update(['password' => $validated['password']]);

        return back()->with('success', __('profile.password_updated'));
    }

    public function updatePin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'pin' => ['required', 'digits_between:4,6', 'confirmed'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->update(['approval_pin' => $validated['pin']]);

        return back()->with('success', __('profile.pin_updated'));
    }

    public function enableTwoFactor(Request $request, Google2FA $google2fa): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->forceFill(['two_factor_secret' => $google2fa->generateSecretKey(32), 'two_factor_confirmed_at' => null])->save();

        return back();
    }

    public function confirmTwoFactor(Request $request, Google2FA $google2fa): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        /** @var User $user */
        $user = $request->user();

        if ($user->two_factor_secret === null || ! $google2fa->verifyKey($user->two_factor_secret, (string) $request->input('code'))) {
            return back()->withErrors(['code' => __('auth.two_factor_invalid')]);
        }

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        activity('security')->causedBy($user)->performedOn($user)->log('two_factor.enabled');

        return back()->with('success', __('profile.two_factor_enabled'));
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);

        /** @var User $user */
        $user = $request->user();
        $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();
        activity('security')->causedBy($user)->performedOn($user)->log('two_factor.disabled');

        return back()->with('success', __('profile.two_factor_disabled'));
    }
}
