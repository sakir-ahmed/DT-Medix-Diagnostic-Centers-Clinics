<?php

declare(strict_types=1);

namespace App\Actions\Referrers;

use App\Models\Referrer;

final class SaveReferrerAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Referrer $referrer = null): Referrer
    {
        $referrer ??= new Referrer;
        $data['mobile'] = normalize_bd_mobile(isset($data['mobile']) && is_string($data['mobile']) ? $data['mobile'] : null);
        $referrer->fill($data)->save();

        return $referrer;
    }
}
