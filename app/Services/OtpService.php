<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DomainException;
use App\Jobs\SendSmsJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * One-time codes: hashed in the cache for 5 minutes, sends limited per
 * IP + mobile (5 per hour), 5 wrong attempts invalidate the code.
 */
final class OtpService
{
    public const TTL_SECONDS = 300;

    public const MAX_SENDS_PER_HOUR = 5;

    public const MAX_ATTEMPTS = 5;

    public function send(string $mobile, string $ip, string $purpose = 'login'): void
    {
        $limiterKey = 'otp-send:'.$ip.'|'.$mobile;

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_SENDS_PER_HOUR)) {
            throw DomainException::make('TOO_MANY_REQUESTS', 429);
        }

        RateLimiter::hit($limiterKey, 3600);

        $code = (string) random_int(100000, 999999);

        Cache::put($this->key($mobile, $purpose), [
            'hash' => Hash::make($code),
            'attempts' => 0,
        ], self::TTL_SECONDS);

        SendSmsJob::dispatch($mobile, __('auth.otp_sms', ['code' => $code, 'minutes' => self::TTL_SECONDS / 60]), 'otp');
    }

    public function verify(string $mobile, string $code, string $purpose = 'login'): bool
    {
        $key = $this->key($mobile, $purpose);
        /** @var array{hash: string, attempts: int}|null $entry */
        $entry = Cache::get($key);

        if ($entry === null) {
            return false;
        }

        if (Hash::check($code, $entry['hash'])) {
            Cache::forget($key);

            return true;
        }

        $entry['attempts']++;

        if ($entry['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($key);
        } else {
            Cache::put($key, $entry, self::TTL_SECONDS);
        }

        return false;
    }

    private function key(string $mobile, string $purpose): string
    {
        return 'otp:'.$purpose.':'.$mobile;
    }
}
