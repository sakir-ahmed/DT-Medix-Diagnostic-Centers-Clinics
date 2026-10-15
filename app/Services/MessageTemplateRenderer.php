<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MessageKey;
use App\Exceptions\DomainException;

/**
 * Fills {placeholders}. Only the placeholders allowed for the message type
 * are accepted, so results and patient identity can never be inserted.
 */
final class MessageTemplateRenderer
{
    /**
     * @param  array<string, string|int|null>  $values
     */
    public function render(string $body, array $values): string
    {
        $text = preg_replace_callback('/\{([a-z_]+)\}/', fn (array $m): string => (string) ($values[$m[1]] ?? ''), $body) ?? $body;

        return trim(preg_replace('/\s{2,}/', ' ', $text) ?? $text);
    }

    /**
     * @return list<string> placeholders used in the body that are not allowed
     */
    public function invalidPlaceholders(MessageKey $key, string $body): array
    {
        preg_match_all('/\{([a-z_]+)\}/', $body, $matches);

        return array_values(array_diff(array_unique($matches[1]), $key->placeholders()));
    }

    public function ensureValid(MessageKey $key, string $body): void
    {
        $invalid = $this->invalidPlaceholders($key, $body);

        if ($invalid !== []) {
            throw DomainException::make('INVALID_PLACEHOLDER', 422, ['placeholders' => $invalid], ['placeholders' => implode(', ', $invalid)]);
        }
    }

    /**
     * Sample values for the template editor preview.
     *
     * @return array<string, string>
     */
    public static function sampleValues(string $center): array
    {
        return [
            'center' => $center, 'doctor' => 'Dr. A. Rahman', 'date' => now()->format('d M'), 'time' => '5:30 PM', 'token' => '12',
            'branch' => 'BR01', 'phone' => '01700000000', 'invoice_no' => 'INV-BR01-26-000123', 'ready_at' => now()->addDay()->format('d M, g A'),
            'link' => 'https://dtmedix.test/r/AbC123', 'pin_hint' => 'PIN: last 4 digits of the invoice', 'amount' => '1,500', 'due' => '500',
            'patients' => '42', 'billed' => '85,400', 'collected' => '79,900', 'dues' => '5,500',
        ];
    }
}
