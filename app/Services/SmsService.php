<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * BdBulkSMS transport only. Never logs the message text.
 */
class SmsService
{
    public function send(string $mobile, string $message): bool
    {
        if (blank(config('services.bdbulksms.token'))) {
            Log::warning('SMS gateway not configured.');

            return false;
        }

        $response = Http::asForm()
            ->timeout(10)
            ->post((string) config('services.bdbulksms.url'), [
                'to' => $mobile,
                'message' => $message,
                'token' => config('services.bdbulksms.token'),
            ]);

        // Confirm the exact success format in the BdBulkSMS API docs.
        $sent = $response->successful() && str_starts_with(trim($response->body()), 'Ok');

        $sent
            ? Log::info('SMS sent.', ['mobile_suffix' => substr($mobile, -3)])
            : Log::error('SMS failed.', ['status' => $response->status()]);

        return $sent;
    }

    /**
     * Number of SMS segments: 160/153 for GSM text, 70/67 for Unicode (Bangla).
     */
    public static function segments(string $message): int
    {
        $length = mb_strlen($message);

        if ($length === 0) {
            return 0;
        }

        $unicode = preg_match('/[^\x00-\x7F]/', $message) === 1;
        [$single, $multi] = $unicode ? [70, 67] : [160, 153];

        return $length <= $single ? 1 : (int) ceil($length / $multi);
    }
}
