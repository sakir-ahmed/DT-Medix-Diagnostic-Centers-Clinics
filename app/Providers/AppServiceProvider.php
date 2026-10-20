<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Support\BranchContext;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(BranchContext::class);

        Scramble::ignoreDefaultRoutes();
    }

    public function boot(): void
    {
        Gate::before(fn (User $user): ?bool => $user->isSuperAdmin() ? true : null);

        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        Password::defaults(fn (): Password => $this->app->isProduction()
            ? Password::min(10)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8));

        $this->configureRateLimits();
        $this->configureApiDocs();
    }

    private function configureRateLimits(): void
    {
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(60)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));

        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)
            ->by(Str::lower((string) $request->input('login')).'|'.$request->ip()));

        RateLimiter::for('public-report', fn (Request $request): Limit => Limit::perMinute(10)->by((string) $request->ip()));
    }

    private function configureApiDocs(): void
    {
        Scramble::configure()
            ->routes(fn (Route $route): bool => Str::startsWith($route->uri, 'api/v1'))
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->secure(SecurityScheme::http('bearer'));
            });

        // Any signed-in staff member or platform admin may read the API docs.
        Gate::define('viewApiDocs', fn (?User $user): bool => $user !== null || ! $this->app->isProduction());

        Scramble::registerUiRoute('api-docs');
        Scramble::registerJsonSpecificationRoute('api-docs.json');
    }
}
