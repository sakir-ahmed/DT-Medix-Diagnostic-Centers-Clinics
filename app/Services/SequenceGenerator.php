<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Sequence;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Gapless counters generated under a row lock. Call inside the same
 * transaction that stores the numbered record so a rollback releases the
 * number again and numbers are never skipped or reused.
 */
final class SequenceGenerator
{
    public function next(string $key): int
    {
        return DB::transaction(function () use ($key): int {
            $sequence = Sequence::query()->where('key', $key)->lockForUpdate()->first();

            if ($sequence === null) {
                try {
                    $sequence = Sequence::query()->create(['key' => $key, 'value' => 0]);
                } catch (UniqueConstraintViolationException) {
                    $sequence = Sequence::query()->where('key', $key)->lockForUpdate()->firstOrFail();
                }
            }

            $sequence->value++;
            $sequence->save();

            return $sequence->value;
        });
    }

    /**
     * Patient ID: P-26-000123 (per year).
     */
    public function patientCode(): string
    {
        $year = now()->format('y');

        return sprintf('P-%s-%06d', $year, $this->next('patient:'.$year));
    }
}
