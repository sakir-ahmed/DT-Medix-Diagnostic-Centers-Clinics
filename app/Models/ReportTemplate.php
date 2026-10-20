<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReportStyle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Table templates (pathology) and narrative templates (imaging).
 *
 * @property int $id
 * @property int|null $department_id
 * @property string $name
 * @property ReportStyle $type
 * @property array<string, mixed>|null $options
 * @property string|null $default_findings
 * @property string|null $default_impression
 * @property string|null $footer_note
 * @property bool $is_active
 */
class ReportTemplate extends Model
{
    public const DEFAULT_OPTIONS = [
        'show_method' => true,
        'show_interpretation' => true,
        'show_previous' => false,
        'show_unit' => true,
        'group_by_label' => true,
    ];

    protected $fillable = ['department_id', 'name', 'type', 'options', 'default_findings', 'default_impression', 'footer_note', 'is_active'];

    protected function casts(): array
    {
        return [
            'type' => ReportStyle::class,
            'options' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedOptions(): array
    {
        return array_merge(self::DEFAULT_OPTIONS, $this->options ?? []);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return HasMany<QuickPhrase, $this>
     */
    public function phrases(): HasMany
    {
        return $this->hasMany(QuickPhrase::class)->orderBy('shortcut');
    }
}
