<?php

declare(strict_types=1);

namespace App\Actions\Branches;

use App\Models\Branch;
use App\Services\PlanGate;
use App\Support\Tenancy\TenantContext;

final class SaveBranchAction
{
    public function __construct(private readonly PlanGate $planGate, private readonly TenantContext $context) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Branch $branch = null): Branch
    {
        if ($branch === null) {
            $tenant = $this->context->tenant();
            abort_if($tenant === null, 404);
            $this->planGate->ensureCanAddBranch($tenant);
            $branch = new Branch;
        }

        $data['code'] = strtoupper((string) $data['code']);
        $branch->fill($data)->save();

        return $branch;
    }

    public function setActive(Branch $branch, bool $active): Branch
    {
        $branch->forceFill(['is_active' => $active])->save();

        return $branch;
    }
}
