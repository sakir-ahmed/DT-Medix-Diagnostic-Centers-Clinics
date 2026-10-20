<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateStaffAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Database\Seeders\DemoStaffSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', array_filter([
            'demo_users' => $this->demoUsers() ?: null,
        ]));
    }

    /**
     * The seeded demo accounts for one-click sign-in, only while demo
     * mode is on outside production.
     *
     * @return list<array<string, string|int>>
     */
    private function demoUsers(): array
    {
        if (! config('app.demo') || app()->isProduction()) {
            return [];
        }

        $order = array_flip(array_values(DemoStaffSeeder::ROLE_USERNAMES));

        return User::query()
            ->where('username', 'like', 'demo.%')
            ->get()
            ->sortBy(fn (User $user): int => $order[explode('.', (string) $user->username)[1] ?? ''] ?? 99)
            ->values()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => (string) $user->username,
                'role' => __('roles.'.$user->getRoleNames()->first()),
            ])
            ->all();
    }

    public function store(LoginRequest $request, AuthenticateStaffAction $authenticate): RedirectResponse
    {
        $user = $authenticate->execute($request);

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put('login.id', $user->id);
            $request->session()->put('login.remember', $request->boolean('remember'));

            return redirect()->route('two-factor.challenge');
        }

        return $this->completeLogin($request, $user, $request->boolean('remember'), $authenticate);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public static function completeLogin(Request $request, User $user, bool $remember, AuthenticateStaffAction $authenticate): RedirectResponse
    {
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();
        $request->session()->put('last_activity_at', now()->timestamp);
        $request->session()->put('locale', $user->locale);
        $authenticate->recordLogin($user);

        return redirect()->intended(route('admin.dashboard'));
    }
}
