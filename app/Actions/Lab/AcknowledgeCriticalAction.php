<?php

declare(strict_types=1);

namespace App\Actions\Lab;

use App\Models\OrderItem;
use App\Models\TestResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Records who was informed about a critical value and when. Required
 * before the report can move on.
 */
final class AcknowledgeCriticalAction
{
    public function execute(OrderItem $item, string $informedName, ?User $actor = null, ?string $informedAt = null): OrderItem
    {
        $informedName = trim($informedName);
        $at = $informedAt === null || $informedAt === '' ? now() : now()->parse($informedAt);

        return DB::transaction(function () use ($item, $informedName, $actor, $at): OrderItem {
            TestResult::query()
                ->where('order_item_id', $item->id)
                ->where('flag', 'C')
                ->whereNull('critical_ack_at')
                ->update([
                    'critical_informed_name' => $informedName,
                    'critical_informed_at' => $at,
                    'critical_ack_by' => $actor?->id,
                    'critical_ack_at' => now(),
                ]);

            $logger = activity('lab')->performedOn($item)->withProperties([
                'item_id' => $item->id,
                'order_id' => $item->order_id,
            ]);

            if ($actor !== null) {
                $logger->causedBy($actor);
            }

            $logger->log('critical.acknowledged');

            return $item->fresh(['results']) ?? $item;
        });
    }
}
