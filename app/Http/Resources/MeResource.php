<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class MeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tenant = $this->tenant;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'locale' => $this->locale,
            'roles' => $this->getRoleNames()->values()->all(),
            'permissions' => $this->getAllPermissions()->pluck('name')->values()->all(),
            'branches' => Branch::query()->orderBy('code')->get()->map(fn (Branch $branch): array => [
                'id' => $branch->id,
                'code' => $branch->code,
                'name' => $branch->name,
            ])->all(),
            'tenant' => $tenant === null ? null : [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'plan' => $tenant->plan->value,
                'status' => $tenant->status->value,
                'trial_days_left' => $tenant->trialDaysLeft(),
                'modules' => $tenant->enabledModules(),
            ],
        ];
    }
}
