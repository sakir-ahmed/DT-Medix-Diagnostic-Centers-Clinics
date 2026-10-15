<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Login with username, email or mobile.
     *
     * @return array<string, string>
     */
    public function getCredentials(): array
    {
        $login = trim($this->string('login')->toString());

        $field = match (true) {
            filter_var($login, FILTER_VALIDATE_EMAIL) !== false => 'email',
            preg_match('/^(\+?88)?01[3-9]\d{8}$/', $login) === 1 => 'mobile',
            default => 'username',
        };

        if ($field === 'mobile') {
            $login = (string) normalize_bd_mobile($login);
        }

        return [$field => $login, 'password' => (string) $this->input('password')];
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'login' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey())]),
        ]);
    }

    public function hitRateLimiter(): void
    {
        RateLimiter::hit($this->throttleKey(), 60);
    }

    public function clearRateLimiter(): void
    {
        RateLimiter::clear($this->throttleKey());
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')->toString()).'|'.$this->ip());
    }
}
