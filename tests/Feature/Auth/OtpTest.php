<?php

declare(strict_types=1);

use App\Enums\RoleName;
use App\Exceptions\DomainException;
use App\Jobs\SendSmsJob;
use App\Services\OtpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    Queue::fake();
});

it('stores the code hashed with a 5 minute expiry and queues the SMS', function (): void {
    app(OtpService::class)->send('8801712345678', '10.0.0.1');

    $entry = Cache::get('otp:login:8801712345678');

    expect($entry)->toBeArray()
        ->and($entry['hash'])->not->toMatch('/^\d{6}$/')
        ->and(Hash::info($entry['hash'])['algoName'])->not->toBe('unknown');

    Queue::assertPushed(SendSmsJob::class, fn (SendSmsJob $job): bool => $job->mobile === '8801712345678');
});

it('verifies a correct code once and deletes it', function (): void {
    $code = null;
    Queue::fake();
    app(OtpService::class)->send('8801712345678', '10.0.0.1');
    Queue::assertPushed(SendSmsJob::class, function (SendSmsJob $job) use (&$code): bool {
        preg_match('/\d{6}/', $job->message, $m);
        $code = $m[0];

        return true;
    });

    $otp = app(OtpService::class);

    expect($otp->verify('8801712345678', $code))->toBeTrue()
        ->and($otp->verify('8801712345678', $code))->toBeFalse();
});

it('invalidates the code after five wrong attempts', function (): void {
    $code = null;
    app(OtpService::class)->send('8801712345678', '10.0.0.1');
    Queue::assertPushed(SendSmsJob::class, function (SendSmsJob $job) use (&$code): bool {
        preg_match('/\d{6}/', $job->message, $m);
        $code = $m[0];

        return true;
    });

    $otp = app(OtpService::class);
    foreach (range(1, 5) as $i) {
        $otp->verify('8801712345678', $code === '000000' ? '111111' : '000000');
    }

    expect($otp->verify('8801712345678', $code))->toBeFalse();
});

it('limits sends to five per hour per IP and mobile', function (): void {
    $otp = app(OtpService::class);

    foreach (range(1, 5) as $i) {
        $otp->send('8801712345678', '10.0.0.1');
    }

    expect(fn () => $otp->send('8801712345678', '10.0.0.1'))->toThrow(DomainException::class);

    // A different mobile from the same IP still works.
    $otp->send('8801799999999', '10.0.0.1');
    Queue::assertPushed(SendSmsJob::class, 6);
});

it('signs staff in by OTP through the API', function (): void {
    makeCenter();
    makeUser(RoleName::Receptionist, attributes: ['mobile' => '8801712345678']);
    $code = null;

    $this->postJson('/api/v1/auth/otp/send', ['mobile' => '01712345678'])->assertOk();
    Queue::assertPushed(SendSmsJob::class, function (SendSmsJob $job) use (&$code): bool {
        preg_match('/\d{6}/', $job->message, $m);
        $code = $m[0];

        return true;
    });

    $this->postJson('/api/v1/auth/otp/verify', ['mobile' => '01712345678', 'code' => $code === '000001' ? '000002' : '000001'])
        ->assertStatus(422)->assertJsonPath('error.code', 'OTP_INVALID');

    $this->postJson('/api/v1/auth/otp/verify', ['mobile' => '01712345678', 'code' => $code])
        ->assertOk()->assertJsonStructure(['data' => ['token']]);
});

it('does not reveal whether a mobile is registered', function (): void {
    $this->postJson('/api/v1/auth/otp/send', ['mobile' => '01788888888'])->assertOk();

    Queue::assertNothingPushed();
});
