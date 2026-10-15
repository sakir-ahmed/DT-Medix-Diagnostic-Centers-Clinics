<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\Sex;
use App\Models\ReferenceRange;
use App\Models\TestParameter;
use Illuminate\Support\Carbon;

/**
 * Chooses the reference range for a patient: a sex-specific range wins over
 * "any", then the narrowest matching age band. Without a date of birth only
 * ranges that cover every age are used.
 */
final class ReferenceRangeResolver
{
    public function resolve(TestParameter $parameter, ?Sex $sex, ?Carbon $dateOfBirth, ?Carbon $at = null): ?ReferenceRange
    {
        $sexValue = $sex?->value;
        $at ??= now();

        return $parameter->ranges
            ->filter(function (ReferenceRange $range) use ($sexValue, $dateOfBirth, $at): bool {
                if ($range->sex !== 'any' && $range->sex !== $sexValue) {
                    return false;
                }

                if ($dateOfBirth === null) {
                    return $range->age_min === 0 && $range->age_max === null;
                }

                return $range->matchesAge($dateOfBirth, $at);
            })
            ->sortBy([
                fn (ReferenceRange $a, ReferenceRange $b): int => ($a->sex === 'any' ? 1 : 0) <=> ($b->sex === 'any' ? 1 : 0),
                fn (ReferenceRange $a, ReferenceRange $b): int => $a->spanDays() <=> $b->spanDays(),
            ])
            ->first();
    }
}
