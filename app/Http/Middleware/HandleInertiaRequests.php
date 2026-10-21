<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\Navigation;
use App\Support\Translations;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * The admin panel and patient portal are client-side only; SSR is used
     * for public pages (landing, booking, report verification).
     *
     * @var list<string>
     */
    protected $withoutSsr = ['admin', 'admin/*', 'login', 'two-factor*', 'portal/*', 'profile*'];

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $context = app(BranchContext::class);
        /** @var User|null $user */
        $user = $request->user('web');

        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
                'locale' => app()->getLocale(),
                'locales' => ['en', 'bn'],
                'idle_timeout' => (int) setting('idle_timeout_minutes', 15),
            ],
            'translations' => fn (): array => Translations::for(app()->getLocale()),
            'auth' => fn (): ?array => $user === null ? null : [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'locale' => $user->locale,
                    'two_factor' => $user->hasTwoFactorEnabled(),
                    'is_super_admin' => $user->isSuperAdmin(),
                ],
                'roles' => $user->getRoleNames()->values()->all(),
                'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            ],
            'center' => fn (): array => [
                'name' => setting('center_name'),
                'name_bn' => setting('center_name_bn'),
            ],
            'branches' => fn (): array => $user === null ? [] : Branch::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name'])
                ->map(fn (Branch $branch): array => ['id' => $branch->id, 'code' => $branch->code, 'name' => $branch->name])
                ->all(),
            'current_branch_id' => $context->currentBranchId(),
            'navigation' => fn (): array => $user === null ? [] : Navigation::for($user),
            'ziggy' => fn (): ?array => $request->is('admin', 'admin/*')
                ? null
                : [...(new Ziggy)->toArray(), 'location' => $request->url()],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'duplicates' => fn () => $request->session()->get('duplicates'),
                'created_patient_id' => fn () => $request->session()->get('created_patient_id'),
                'print_order_id' => fn () => $request->session()->get('print_order_id'),
            ],
        ];
    }
}
