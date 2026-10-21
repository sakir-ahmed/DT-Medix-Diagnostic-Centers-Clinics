<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs staff out after a period without activity (default 15 minutes).
 */
class IdleTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession() || Auth::guard('web')->guest()) {
            return $next($request);
        }

        $timeout = (int) setting('idle_timeout_minutes', config('session.idle_timeout', 15));
        $last = $request->session()->get('last_activity_at');

        if ($last !== null && now()->timestamp - (int) $last > $timeout * 60) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('warning', __('auth.idle_logged_out'));
        }

        $request->session()->put('last_activity_at', now()->timestamp);

        return $next($request);
    }
}
