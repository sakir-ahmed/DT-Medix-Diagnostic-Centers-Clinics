<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageKey;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $tenant_id
 * @property MessageKey $key
 * @property string $channel
 * @property string $body_en
 * @property string $body_bn
 * @property string|null $whatsapp_template
 * @property bool $is_active
 */
class MessageTemplate extends Model
{
    use BelongsToTenant;
    use LogsActivity;

    protected $fillable = ['key', 'channel', 'body_en', 'body_bn', 'whatsapp_template', 'is_active'];

    protected function casts(): array
    {
        return ['key' => MessageKey::class, 'is_active' => 'boolean'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['key', 'channel', 'body_en', 'body_bn', 'is_active'])->logOnlyDirty();
    }

    public function body(string $locale): string
    {
        return $locale === 'bn' ? $this->body_bn : $this->body_en;
    }
}
