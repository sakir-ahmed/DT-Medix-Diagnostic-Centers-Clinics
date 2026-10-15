<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\ResultType;
use App\Models\ReferenceRange;

/**
 * Result flags (same rules as resources/js/lib/flags.js):
 * C critical, H high, L low, A abnormal choice, N normal.
 */
final class ResultFlagger
{
    /**
     * @param  list<string>|null  $abnormalValues
     */
    public function flag(?string $value, ResultType $type, ?ReferenceRange $range, ?array $abnormalValues = null): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        if ($type === ResultType::Choice || $type === ResultType::Text) {
            $abnormal = array_map('mb_strtolower', $abnormalValues ?? []);

            if ($abnormal !== [] && in_array(mb_strtolower(trim($value)), $abnormal, true)) {
                return 'A';
            }

            return $type === ResultType::Choice ? 'N' : null;
        }

        if (preg_match('/^-?\d+(\.\d+)?$/', trim($value)) !== 1 || $range === null) {
            return null;
        }

        $number = trim($value);
        $cmp = fn (?string $limit): ?int => $limit === null ? null : bccomp($number, $limit, 4);

        if (($cmp($range->critical_low) ?? 1) <= 0 || ($cmp($range->critical_high) ?? -1) >= 0) {
            return 'C';
        }

        if (($cmp($range->low) ?? 0) < 0) {
            return 'L';
        }

        if (($cmp($range->high) ?? 0) > 0) {
            return 'H';
        }

        return 'N';
    }
}
