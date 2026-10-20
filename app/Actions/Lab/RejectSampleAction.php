<?php

declare(strict_types=1);

namespace App\Actions\Lab;

use App\Enums\OrderItemStatus;
use App\Enums\SampleStatus;
use App\Events\SampleUpdated;
use App\Exceptions\DomainException;
use App\Models\OrderItem;
use App\Models\Sample;
use App\Models\User;
use App\Services\Billing\DocumentNumbers;
use Illuminate\Support\Facades\DB;

/**
 * Rejects a sample with a reason. With recollection, a fresh pending
 * sample is created for the same tests and the tests move back to it.
 */
final class RejectSampleAction
{
    public function __construct(private readonly DocumentNumbers $numbers) {}

    public function execute(Sample $sample, string $reason, bool $requestRecollection, ?User $actor = null): Sample
    {
        if (! in_array($sample->status, [SampleStatus::Collected, SampleStatus::Received], true)) {
            throw DomainException::make('INVALID_STATE', 422);
        }

        if ($sample->items()->whereIn('status', [OrderItemStatus::Entered, OrderItemStatus::Verified, OrderItemStatus::Released])->exists()) {
            throw DomainException::make('INVALID_STATE', 422);
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw DomainException::make('REASON_REQUIRED', 422);
        }

        return DB::transaction(function () use ($sample, $reason, $requestRecollection, $actor): Sample {
            $sample->forceFill([
                'status' => SampleStatus::Rejected,
                'reject_reason' => $reason,
            ])->save();

            $replacement = null;

            if ($requestRecollection) {
                $replacement = Sample::query()->create([
                    'branch_id' => $sample->branch_id,
                    'order_id' => $sample->order_id,
                    'patient_id' => $sample->patient_id,
                    'sample_code' => $this->numbers->sample($sample->branch),
                    'sample_type' => $sample->sample_type,
                    'container' => $sample->container,
                    'status' => SampleStatus::Pending,
                    'recollection_of_id' => $sample->id,
                ]);

                $sample->items()
                    ->whereNotIn('status', [OrderItemStatus::Cancelled])
                    ->each(function (OrderItem $item) use ($replacement): void {
                        $item->forceFill([
                            'sample_id' => $replacement->id,
                            'status' => OrderItemStatus::Ordered,
                            'expected_at' => now()->addHours($item->tat_hours),
                        ])->save();
                    });
            }

            SampleUpdated::dispatch($sample);

            if ($replacement !== null) {
                SampleUpdated::dispatch($replacement);
            }

            $logger = activity('lab')->performedOn($sample)->withProperties([
                'recollection_requested' => $requestRecollection,
                'replacement_sample_id' => $replacement?->id,
            ]);

            if ($actor !== null) {
                $logger->causedBy($actor);
            }

            $logger->log('sample.rejected');

            return $sample->fresh(['items']) ?? $sample;
        });
    }
}
