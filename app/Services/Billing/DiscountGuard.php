<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Support\Money;

/**
 * Turns a percent or fixed discount into an amount and says whether it
 * sits above the center's approval limit. A free case is 100 percent.
 */
final class DiscountGuard
{
    /**
     * @return array{type: string, value: string, amount: string, exceeds: bool}|null
     */
    public function assess(?string $type, ?string $value, string $category, string $subtotal): ?array
    {
        if ($category === 'free') {
            $type = 'percent';
            $value = '100';
        }

        if ($type !== 'percent' && $type !== 'fixed') {
            return null;
        }

        $value = Money::of($value ?? '0');

        if ($type === 'percent') {
            $value = Money::min($value, '100');
        }

        if (Money::isZero($value) || Money::isZero($subtotal)) {
            return null;
        }

        $amount = $type === 'percent'
            ? Money::percent($subtotal, $value)
            : Money::min($value, $subtotal);

        if (Money::isZero($amount)) {
            return null;
        }

        $limit = (string) (center()?->setting('discount_limit_percent') ?? 10);

        return [
            'type' => $type,
            'value' => $value,
            'amount' => $amount,
            'exceeds' => Money::compare(Money::percentOf($amount, $subtotal), $limit) > 0,
        ];
    }
}
