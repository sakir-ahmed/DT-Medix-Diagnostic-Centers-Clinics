<?php

declare(strict_types=1);

namespace App\Actions\Patients;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Duplicate detection: same mobile and a similar name.
 */
final class FindDuplicatePatientsAction
{
    private const SIMILARITY_THRESHOLD = 70.0;

    /**
     * @return Collection<int, Patient>
     */
    public function execute(?string $mobile, string $name, ?int $ignoreId = null): Collection
    {
        $mobile = normalize_bd_mobile($mobile);

        if ($mobile === null) {
            return new Collection;
        }

        return Patient::query()
            ->where('mobile', $mobile)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->limit(20)
            ->get()
            ->filter(fn (Patient $patient): bool => self::similar($patient->name, $name))
            ->values();
    }

    public static function similar(string $a, string $b): bool
    {
        $a = self::normalize($a);
        $b = self::normalize($b);

        if ($a === '' || $b === '') {
            return false;
        }

        if ($a === $b || str_contains($a, $b) || str_contains($b, $a)) {
            return true;
        }

        similar_text($a, $b, $percent);

        return $percent >= self::SIMILARITY_THRESHOLD;
    }

    private static function normalize(string $name): string
    {
        $name = Str::lower(Str::ascii($name));
        $name = preg_replace('/\b(md|mohammad|mohammed|muhammad|mst|mosammat|mrs|mr|ms|dr|sk|sheikh)\b\.?/', ' ', $name) ?? $name;

        return trim(preg_replace('/[^a-z\s]+|\s+/', ' ', $name) ?? '');
    }
}
