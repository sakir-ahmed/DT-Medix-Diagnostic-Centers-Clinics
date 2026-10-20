<?php

declare(strict_types=1);

namespace App\Actions\Branches;

use App\Models\Branch;

final class SaveBranchAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Branch $branch = null): Branch
    {
        $branch ??= new Branch;

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
