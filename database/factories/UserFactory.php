<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => Str::lower(Str::random(10)),
            'email' => fake()->unique()->safeEmail(),
            'mobile' => '88017'.fake()->unique()->numerify('########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= 'password',
            'locale' => 'en',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }
}
