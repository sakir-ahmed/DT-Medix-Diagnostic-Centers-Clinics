<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(['locale' => ['required', Rule::in(SetLocale::SUPPORTED)]]);

        $request->session()->put('locale', $validated['locale']);

        $user = $request->user();

        if ($user instanceof User) {
            $user->forceFill(['locale' => $validated['locale']])->saveQuietly();
        }

        return back();
    }
}
