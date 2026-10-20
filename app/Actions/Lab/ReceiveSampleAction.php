<?php

declare(strict_types=1);

namespace App\Actions\Lab;

use App\Enums\OrderItemStatus;
use App\Enums\SampleStatus;
use App\Events\SampleUpdated;
use App\Exceptions\DomainException;
use App\Models\Sample;
use App\Models\User;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The lab receives a collected sample. A sample from another branch gets a
 * clear error instead of a silent success.
 */
final class ReceiveSampleAction
{
    public function __construct(private readonly BranchContext $context) {}

    public function execute(Sample $sample, ?User $actor = null): Sample
    {
        if ($sample->status !== SampleStatus::Collected) {
            throw DomainException::make('INVALID_STATE', 422);
        }

        $currentBranch = $this->context->currentBranchId();

        if ($currentBranch !== null && $sample->branch_id !== $currentBranch) {
            throw DomainException::make('SAMPLE_WRONG_BRANCH', 409);
        }

        return DB::transaction(function () use ($sample, $actor): Sample {
            $sample->forceFill([
                'status' => SampleStatus::Received,
                'received_by' => $actor?->id,
                'received_at' => now(),
            ])->save();

            $sample->items()
                ->where('status', OrderItemStatus::Collected)
                ->update(['status' => OrderItemStatus::Received]);

            SampleUpdated::dispatch($sample);
            $this->log($actor, $sample, 'sample.received');

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
