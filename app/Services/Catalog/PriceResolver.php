<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Models\BranchTestPrice;
use App\Models\Test;

/**
 * Branch price list first (when set), then the catalog price.
 */
final class PriceResolver
{
    public function forTest(Test $test, ?int $branchId): string
    {
        if ($branchId !== null) {
            $price = BranchTestPrice::query()->withoutGlobalScope('branch')->where('branch_id', $branchId)->where('test_id', $test->id)->value('price');

            if ($price !== null) {
                return money((string) $price);
            }
        }

        return money($test->price);
    }
}
