<?php

declare(strict_types=1);

use App\Exceptions\DomainException;
use App\Http\Middleware\EnsureBranchAccess;
use App\Http\Middleware\EnsureDemoMode;
use App\Http\Middleware\EnsureIdempotency;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IdleTimeout;
use App\Http\Middleware\ResolveBranch;
use App\Http\Middleware\SetLocale;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->api(append: [
            SetLocale::class,
        ]);

        $middleware->alias([
            'branch.scope' => ResolveBranch::class,
            'idle' => IdleTimeout::class,
            'idempotent' => EnsureIdempotency::class,
            'branch' => EnsureBranchAccess::class,
            'demo.mode' => EnsureDemoMode::class,
            'locale' => SetLocale::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);

        $middleware->priority([
            StartSession::class,
            SetLocale::class,
            Authenticate::class,
            ResolveBranch::class,
            SubstituteBindings::class,
        ]);

        $middleware->validateCsrfTokens(except: ['payments/*/callback', 'payments/*/ipn', 'webhooks/*']);
        $middleware->redirectGuestsTo(fn (Request $request): string => $request->is('portal', 'portal/*') ? route('portal.login') : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApi = fn (Request $request): bool => $request->is('api/*') || ($request->expectsJson() && ! $request->header('X-Inertia'));

        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => $isApi($request));

        $exceptions->dontReport([DomainException::class]);

        $exceptions->render(function (DomainException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->details);
            }

            if (in_array($e->errorCode, ['TENANT_SUSPENDED'], true) && $request->isMethodSafe()) {
                return Inertia::render('Error', ['status' => 403, 'code' => $e->errorCode, 'message' => $e->getMessage()])
                    ->toResponse($request)->setStatusCode(403);
            }

            return back()->with('error', $e->getMessage())->with('error_code', $e->errorCode);
        });

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if ($isApi($request)) {
                return ApiResponse::error('VALIDATION_FAILED', __('errors.VALIDATION_FAILED'), 422, $e->errors());
            }

            return null;
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($isApi) {
            return $isApi($request) ? ApiResponse::error('UNAUTHENTICATED', __('errors.UNAUTHENTICATED'), 401) : null;
        });

        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($isApi) {
            return $isApi($request) ? ApiResponse::error('TOO_MANY_REQUESTS', __('errors.TOO_MANY_REQUESTS'), 429) : null;
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            [$status, $code] = match (true) {
                $e instanceof AuthorizationException, $e instanceof AccessDeniedHttpException, $e instanceof UnauthorizedException => [403, 'FORBIDDEN'],
                $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => [404, 'NOT_FOUND'],
                $e instanceof HttpExceptionInterface => [$e->getStatusCode(), match ($e->getStatusCode()) {
                    401 => 'UNAUTHENTICATED', 403 => 'FORBIDDEN', 404 => 'NOT_FOUND', 429 => 'TOO_MANY_REQUESTS', default => 'SERVER_ERROR',
                }],
                default => [500, 'SERVER_ERROR'],
            };

            $details = $status === 500 ? ['reference' => (string) Str::uuid()] : [];

            return ApiResponse::error($code, __('errors.'.$code), $status, $details);
        });

        $exceptions->respond(function ($response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            if ($request->is('api/*') || ! in_array($status, [403, 404, 500, 503], true)) {
                return $response;
            }

            if ($status >= 500 && app()->hasDebugModeEnabled()) {
                return $response;
            }

            return Inertia::render('Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
