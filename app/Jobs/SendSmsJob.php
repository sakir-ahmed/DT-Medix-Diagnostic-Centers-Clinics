<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends a transactional SMS (OTP and similar) through the queue so a slow
 * gateway never blocks a request.
 */
class SendSmsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $mobile,
        public readonly string $message,
        public readonly string $purpose = 'transactional',
    ) {}

    public function handle(SmsService $sms): void
    {
        $sms->send($this->mobile, $this->message);
    }
}
