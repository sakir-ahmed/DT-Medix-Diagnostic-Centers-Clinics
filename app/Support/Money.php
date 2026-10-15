<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Decimal money arithmetic on strings (BDT, 2 decimals). Never uses floats.
 */
final class Money
{
    private const SCALE = 2;

    public static function of(string|int|null $amount): string
    {
        return self::round((string) ($amount ?? '0'));
    }

    public static function add(string ...$amounts): string
    {
        $total = '0';

        foreach ($amounts as $amount) {
            $total = bcadd($total, self::normalize($amount), 6);
        }

        return self::round($total);
    }

    public static function sub(string $a, string $b): string
    {
        return self::round(bcsub(self::normalize($a), self::normalize($b), 6));
    }

    public static function mul(string $a, string|int $b): string
    {
        return self::round(bcmul(self::normalize($a), self::normalize((string) $b), 6));
    }

    /**
     * The given percent of an amount, rounded half up to 2 decimals.
     */
    public static function percent(string $amount, string|int $percent): string
    {
        return self::round(bcdiv(bcmul(self::normalize($amount), self::normalize((string) $percent), 6), '100', 6));
    }

    /**
     * What percent $part is of $whole (1 decimal), "0.0" when whole is zero.
     */
    public static function percentOf(string $part, string $whole): string
    {
        if (self::isZero($whole)) {
            return '0.0';
        }

        $value = bcdiv(bcmul(self::normalize($part), '100', 6), self::normalize($whole), 6);

        return self::roundTo($value, 1);
    }

    public static function compare(string $a, string $b): int
    {
        return bccomp(self::normalize($a), self::normalize($b), self::SCALE);
    }

    public static function isZero(string $amount): bool
    {
        return self::compare($amount, '0') === 0;
    }

    public static function isNegative(string $amount): bool
    {
        return self::compare($amount, '0') < 0;
    }

    public static function max(string $a, string $b): string
    {
        return self::compare($a, $b) >= 0 ? self::of($a) : self::of($b);
    }

    public static function min(string $a, string $b): string
    {
        return self::compare($a, $b) <= 0 ? self::of($a) : self::of($b);
    }

    public static function round(string $amount): string
    {
        return self::roundTo($amount, self::SCALE);
    }

    private static function roundTo(string $amount, int $scale): string
    {
        $amount = self::normalize($amount);
        $offset = '0.'.str_repeat('0', $scale).'5';

        $rounded = str_starts_with($amount, '-')
            ? bcsub($amount, $offset, $scale)
            : bcadd($amount, $offset, $scale);

        return $rounded === '-0.00' || $rounded === '-0.0' ? ltrim($rounded, '-') : $rounded;
    }

    /**
     * @return numeric-string
     */
    private static function normalize(string $amount): string
    {
        $amount = trim($amount);

        if ($amount === '' || ! is_numeric($amount)) {
            return '0';
        }

        if (stripos($amount, 'e') !== false) {
            $amount = sprintf('%.6F', (float) $amount);
        }

        /** @var numeric-string $amount */
        return $amount;
    }
}
