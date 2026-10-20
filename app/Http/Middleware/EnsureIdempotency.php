<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\DomainException;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * POST endpoints that create money or messages require an Idempotency-Key.
 * The same key within 24 hours returns the original response. A placeholder
 * row is reserved first, so two concurrent requests never both run.
 */
class EnsureIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) $request->header('Idempotency-Key', '');

        if ($key === '' || strlen($key) > 120) {
            throw DomainException::make('IDEMPOTENCY_KEY_REQUIRED', 422);
        }

        $scope = (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());
        $table = DB::table('idempotency_keys');

        DB::table('idempotency_keys')->where('scope', $scope)->where('key', $key)->where('created_at', '<', now()->subDay())->delete();

        try {
            $table->insert([
                'scope' => $scope,
                'key' => $key,
                'method' => $request->method(),
                'path' => mb_substr($request->path(), 0, 255),
                'status' => 0,
                'body' => '',
                'created_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            $existing = DB::table('idempotency_keys')->where('scope', $scope)->where('key', $key)->first();

            if ($existing === null || (int) $existing->status === 0) {
                throw DomainException::make('IDEMPOTENCY_IN_PROGRESS', 409);
            }

            return new JsonResponse(json_decode((string) $existing->body, true), (int) $existing->status, ['Idempotent-Replayed' => 'true']);
        }

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            DB::table('idempotency_keys')->where('scope', $scope)->where('key', $key)->delete();

            throw $e;
        }

        if ($response instanceof JsonResponse && $response->getStatusCode() < 500 && $response->getStatusCode() !== 409 && $response->getStatusCode() !== 422) {
            DB::table('idempotency_keys')->where('scope', $scope)->where('key', $key)->update([
                'status' => $response->getStatusCode(),
                'body' => (string) $response->getContent(),
            ]);
        } else {
            DB::table('idempotency_keys')->where('scope', $scope)->where('key', $key)->delete();
        }

        return $response;
    }
}
