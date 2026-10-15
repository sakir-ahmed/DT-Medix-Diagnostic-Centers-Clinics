<?php

declare(strict_types=1);

use App\Enums\RoleName;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function (): void {
    $this->tenant = makeTenant(['slug' => 'alpha']);
    $this->user = makeUser($this->tenant, RoleName::Receptionist, attributes: [
        'username' => 'rina', 'email' => 'rina@example.test', 'mobile' => '8801712345678', 'password' => 'secret-pass',
    ]);
});

it('signs in with username, email or mobile', function (string $login): void {
    $this->post(route('login.store'), ['login' => $login, 'password' => 'secret-pass'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($this->user);
})->with(['rina', 'rina@example.test', '01712345678', '+8801712345678']);

it('rejects a wrong password with a field error', function (): void {
    $this->post(route('login.store'), ['login' => 'rina', 'password' => 'wrong'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

it('rejects inactive users', function (): void {
    $this->user->forceFill(['is_active' => false])->save();

    $this->post(route('login.store'), ['login' => 'rina', 'password' => 'secret-pass'])->assertSessionHasErrors('login');
    $this->assertGuest();
});

it('rejects users of another tenant on a tenant host', function (): void {
    makeTenant(['slug' => 'beta']);

    $this->post('http://beta.dtmedix.test/login', ['login' => 'rina', 'password' => 'secret-pass'])->assertSessionHasErrors('login');
    $this->assertGuest();
});

it('throttles after five failed attempts', function (): void {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['login' => 'rina', 'password' => 'wrong']);
    }

    $this->post(route('login.store'), ['login' => 'rina', 'password' => 'secret-pass'])->assertSessionHasErrors('login');
    $this->assertGuest();
    RateLimiter::clear('rina|127.0.0.1');
});

it('asks for the TOTP code when two-step verification is on', function (): void {
    $google2fa = app(Google2FA::class);
    $secret = $google2fa->generateSecretKey(32);
    $this->user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();

    $this->post(route('login.store'), ['login' => 'rina', 'password' => 'secret-pass'])
        ->assertRedirect(route('two-factor.challenge'));
    $this->assertGuest();

    $this->post(route('two-factor.store'), ['code' => '000000'])->assertSessionHasErrors('code');

    $this->post(route('two-factor.store'), ['code' => $google2fa->getCurrentOtp($secret)])
        ->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($this->user);
});

it('signs out after the idle timeout', function (): void {
    $this->actingAs($this->user)
        ->withSession(['last_activity_at' => now()->subMinutes(16)->timestamp])
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('keeps an active session', function (): void {
    $this->actingAs($this->user)
        ->withSession(['last_activity_at' => now()->subMinutes(5)->timestamp])
        ->get(route('admin.dashboard'))
        ->assertOk();
});

it('issues and revokes an API token', function (): void {
    $response = $this->postJson('/api/v1/auth/login', ['login' => 'rina', 'password' => 'secret-pass'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['token', 'user' => ['roles', 'permissions', 'branches', 'tenant' => ['plan', 'modules']]]]);

    $token = $response->json('data.token');

    $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.username', 'rina');
    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

    expect($this->user->tokens()->count())->toBe(0);
});

it('returns the error envelope for a bad API login', function (): void {
    $this->postJson('/api/v1/auth/login', ['login' => 'rina', 'password' => 'nope'])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('error.code', 'VALIDATION_FAILED')
        ->assertJsonStructure(['error' => ['details' => ['login']]]);
});

it('returns UNAUTHENTICATED without a token', function (): void {
    $this->getJson('/api/v1/auth/me')->assertStatus(401)->assertJsonPath('error.code', 'UNAUTHENTICATED');
});

it('redirects a guest to the login page', function (): void {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});
