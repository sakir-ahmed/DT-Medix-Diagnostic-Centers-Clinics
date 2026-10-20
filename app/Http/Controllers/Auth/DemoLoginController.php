<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Signs in a seeded demo account with one click. Reachable only when
 * demo mode is on and the environment is not production (the route
 * carries the demo.mode middleware) and only for the seeded demo
 * accounts, never the hidden super-admin.
 */
class DemoLoginController extends Controller
{
    public function store(Request $request, User $user): RedirectResponse
    {
        abort_unless(str_starts_with((string) $user->username, 'demo.') && ! $user->isSuperAdmin(), 404);
        abort_if($user->hasTwoFactorEnabled(), 404);

        Auth::guard('web')->login($user, false);
        $request->session()->regenerate();
        $request->session()->put('last_activity_at', now()->timestamp);
        $request->session()->put('locale', $user->locale);

        return redirect()->route('admin.dashboard');
    }
}
