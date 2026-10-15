<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Referrer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Referrer
 */
class ReferrerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $canSeePayment = $request->user()?->can('commissions.view') ?? false;

        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'code' => $this->code,
            'name' => $this->name,
            'organization' => $this->organization,
            'specialty' => $this->specialty,
            'mobile' => $this->mobile === null ? null : substr($this->mobile, 2),
            'email' => $this->email,
            'address' => $this->address,
            'payment_method' => $this->payment_method,
            'payment_account' => $canSeePayment ? $this->payment_account : null,
            'bank_name' => $canSeePayment ? $this->bank_name : null,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
        ];
    }
}
