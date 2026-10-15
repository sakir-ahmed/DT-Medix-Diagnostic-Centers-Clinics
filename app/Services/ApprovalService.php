<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Manager approval at the counter: the approver's login (username, email
 * or mobile) and approval PIN, checked against the required permission.
 */
final class ApprovalService
{
    public function verify(?string $login, ?string $pin, string $permission, string $throttleKey): User
    {
        $key = 'approval:'.$throttleKey;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw DomainException::make('TOO_MANY_REQUESTS', 429);
        }

        $login = trim((string) $login);
        $mobile = normalize_bd_mobile($login);

        /** @var User|null $approver */
        $approver = $login === '' ? null : User::query()
            ->where(fn ($q) => $q->where('username', $login)->orWhere('email', $login)->when($mobile !== null, fn ($w) => $w->orWhere('mobile', $mobile)))
            ->where('is_active', true)
            ->first();

        $valid = $approver !== null
            && $approver->approval_pin !== null
            && Hash::check((string) $pin, $approver->approval_pin)
            && $approver->can($permission);

        if (! $valid) {
            RateLimiter::hit($key, 300);

            throw DomainException::make('APPROVAL_INVALID', 403);
        }

        RateLimiter::clear($key);

        return $approver;
    }
}
