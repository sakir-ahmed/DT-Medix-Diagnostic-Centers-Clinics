<?php

declare(strict_types=1);

namespace App\Actions\Platform;

use App\Enums\Plan;
use App\Models\Tenant;

final class UpdateTenantPlanAction
{
    /**
     * @param  array{plan: string, name?: string, trial_ends_at?: string|null, subscription_ends_at?: string|null, limits?: array{branches?: int|null, users?: int|null}, modules?: list<string>, message_credits?: int|null}  $data
     */
    public function execute(Tenant $tenant, array $data): Tenant
    {
        $plan = Plan::from($data['plan']);

        $tenant->fill([
            'name' => $data['name'] ?? $tenant->name,
            'plan' => $plan,
            'trial_ends_at' => $data['trial_ends_at'] ?? $tenant->trial_ends_at,
            'subscription_ends_at' => $data['subscription_ends_at'] ?? $tenant->subscription_ends_at,
            'limits' => [
                'branches' => isset($data['limits']['branches']) ? (int) $data['limits']['branches'] : null,
                'users' => isset($data['limits']['users']) ? (int) $data['limits']['users'] : null,
            ],
            'modules' => array_values($data['modules'] ?? $plan->modules()),
        ]);

        if (isset($data['message_credits'])) {
            $tenant->message_credits = (int) $data['message_credits'];
        }

        $tenant->save();

        return $tenant;
    }
}
