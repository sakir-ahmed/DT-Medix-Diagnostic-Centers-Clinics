<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locale from user preference, then session, then Accept-Language (API),
 * then the default "en".
 */
class SetLocale
{
    public const SUPPORTED = ['en', 'bn'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        if ($request->is('api/*')) {
            $locale = $request->getPreferredLanguage(self::SUPPORTED);
        }

        $user = $request->user();

        if ($locale === null && $user instanceof User) {
            $locale = $user->locale;
        }

        if ($locale === null && $request->hasSession()) {
            $locale = $request->session()->get('locale');
        }

        app()->setLocale(in_array($locale, self::SUPPORTED, true) ? $locale : 'en');

        return $next($request);
    }
}
