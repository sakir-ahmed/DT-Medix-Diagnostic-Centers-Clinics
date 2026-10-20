<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Reference range by sex and age. The age band uses one unit (days, months
 * or years), the minimum is inclusive and the maximum exclusive, compared
 * with the patient's completed age in that unit.
 *
 * @property int $id
 * @property int $test_parameter_id
 * @property string $sex
 * @property int $age_min
 * @property int|null $age_max
 * @property string $age_unit
 * @property string|null $low
 * @property string|null $high
 * @property string|null $critical_low
 * @property string|null $critical_high
 * @property string|null $display_text
 */
class ReferenceRange extends Model
{
    public const AGE_UNITS = ['days', 'months', 'years'];

    protected $fillable = ['sex', 'age_min', 'age_max', 'age_unit', 'low', 'high', 'critical_low', 'critical_high', 'display_text'];

    protected function casts(): array
    {
        return [
            'age_min' => 'integer',
            'age_max' => 'integer',
            'low' => 'decimal:4',
            'high' => 'decimal:4',
            'critical_low' => 'decimal:4',
            'critical_high' => 'decimal:4',
        ];
    }

    /**
     * @return BelongsTo<TestParameter, $this>
     */
    public function parameter(): BelongsTo
    {
        return $this->belongsTo(TestParameter::class, 'test_parameter_id');
    }

    /**
     * "13 - 17", "< 200", "> 40" or the custom display text.
     */
    public function text(): string
    {
        if ($this->display_text !== null && $this->display_text !== '') {
            return $this->display_text;
        }

        $low = $this->low === null ? null : self::trim($this->low);
        $high = $this->high === null ? null : self::trim($this->high);

        return match (true) {
            $low !== null && $high !== null => "{$low} - {$high}",
            $low !== null => "> {$low}",
            $high !== null => "< {$high}",
            default => '',
        };
    }

    /**
     * Approximate width of the band in days (to prefer narrower bands).
     */
    public function spanDays(): int
    {
        $factor = match ($this->age_unit) {
            'days' => 1,
            'months' => 30,
            default => 365,
        };

        return $this->age_max === null ? PHP_INT_MAX : ($this->age_max - $this->age_min) * $factor;
    }

    public function matchesAge(Carbon $dateOfBirth, Carbon $at): bool
    {
        $from = $dateOfBirth->copy()->startOfDay();
        $to = $at->copy()->startOfDay();

        $age = (int) match ($this->age_unit) {
            'days' => $from->diffInDays($to),
            'months' => $from->diffInMonths($to),
            default => $from->diffInYears($to),
        };

        return $age >= $this->age_min && ($this->age_max === null || $age < $this->age_max);
    }

    public static function trim(string $number): string
    {
        return str_contains($number, '.') ? rtrim(rtrim($number, '0'), '.') : $number;
    }
}
