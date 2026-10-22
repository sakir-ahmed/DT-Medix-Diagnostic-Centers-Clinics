<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\HolidayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Center-wide holiday (applies to every branch).
 *
 * @property int $id
 * @property Carbon $holiday_on
 * @property string $name
 */
class Holiday extends Model
{
    /** @use HasFactory<HolidayFactory> */
    use HasFactory;

    protected $fillable = ['holiday_on', 'name'];

    protected function casts(): array
    {
        return ['holiday_on' => 'date:Y-m-d'];
    }
}
