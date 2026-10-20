<?php

declare(strict_types=1);

namespace App\Actions\Lab;

use App\Enums\OrderItemStatus;
use App\Events\CriticalResultRaised;
use App\Events\ResultSubmitted;
use App\Exceptions\DomainException;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Moves results to verification. A critical value must be acknowledged
 * before the report can move on.
 */
final class SubmitResultsAction
{
    public function execute(OrderItem $item, ?User $actor = null): OrderItem
    {
        if (! in_array($item->status, [OrderItemStatus::Received, OrderItemStatus::Entered], true)) {
            throw DomainException::make('INVALID_STATE', 422);
        }

        return DB::transaction(function () use ($item, $actor): OrderItem {
            $item->loadMissing('results', 'test.parameters', 'sample', 'order', 'narrative');

            $hasParameters = $item->test->parameters->where('is_active', true)->isNotEmpty();

            if ($hasParameters && $item->results->filter(fn ($result): bool => filled($result->value))->isEmpty()) {
                throw DomainException::make('RESULTS_EMPTY', 422);
            }

            if (! $hasParameters && $item->narrative === null) {
                throw DomainException::make('RESULTS_EMPTY', 422);
            }

            if ($item->hasUnacknowledgedCritical()) {
                throw DomainException::make('CRITICAL_NOT_ACKNOWLEDGED', 409);
            }

            $critical = $item->results->contains(fn ($result): bool => $result->flag === 'C');

            $item->forceFill(['status' => OrderItemStatus::Entered])->save();

            ResultSubmitted::dispatch($item);

            if ($critical) {
                CriticalResultRaised::dispatch($item);
            }

            $logger = activity('lab')->performedOn($item)->withProperties([
                'item_id' => $item->id,
                'order_id' => $item->order_id,
                'critical' => $critical,
            ]);

            if ($actor !== null) {
                $logger->causedBy($actor);
            }

            $logger->log('results.submitted');

            return $item->fresh(['results']) ?? $item;
        });
    }
}
