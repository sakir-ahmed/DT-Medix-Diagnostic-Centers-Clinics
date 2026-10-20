<?php

declare(strict_types=1);

namespace App\Actions\Lab;

use App\Enums\OrderItemStatus;
use App\Enums\SampleStatus;
use App\Events\SampleUpdated;
use App\Exceptions\DomainException;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Marks a pending sample as collected and moves its tests along.
 */
final class CollectSampleAction
{
    public function execute(Sample $sample, ?User $actor = null): Sample
    {
        if ($sample->status !== SampleStatus::Pending) {
            throw DomainException::make('INVALID_STATE', 422);
        }

        return DB::transaction(function () use ($sample, $actor): Sample {
            $sample->forceFill([
                'status' => SampleStatus::Collected,
                'collected_by' => $actor?->id,
                'collected_at' => now(),
            ])->save();

            $sample->items()
                ->where('status', OrderItemStatus::Ordered)
                ->update(['status' => OrderItemStatus::Collected]);

            SampleUpdated::dispatch($sample);
            $this->log($actor, $sample, 'sample.collected');

            return $sample->fresh(['items']) ?? $sample;
        });
    }

    private function log(?User $actor, Model $subject, string $event): void
    {
        $logger = activity('lab')->performedOn($subject);

        if ($actor !== null) {
            $logger->causedBy($actor);
        }

        $logger->log($event);
    }
}
