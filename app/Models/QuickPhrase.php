<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * A reusable sentence inserted with "/" in narrative reports.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $report_template_id
 * @property string $shortcut
 * @property string $section
 * @property string $text
 */
class QuickPhrase extends Model
{
    use BelongsToTenant;

    protected $fillable = ['report_template_id', 'shortcut', 'section', 'text'];
}
