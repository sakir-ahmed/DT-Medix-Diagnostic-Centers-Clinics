<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Lab\AcknowledgeCriticalAction;
use App\Actions\Lab\SaveResultsAction;
use App\Actions\Lab\SubmitResultsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcknowledgeCriticalRequest;
use App\Http\Requests\SaveResultsRequest;
use App\Http\Resources\SampleResource;
use App\Http\Responses\ApiResponse;
use App\Models\Branch;
use App\Models\OrderItem;
use App\Models\Sample;
use App\Services\Lab\ResultRows;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Lab
 */
class LabController extends Controller
{
    /**
     * Samples waiting in the lab, with TAT target times.
     */
    public function worklist(Branch $branch, Request $request): JsonResponse
    {
        $departmentId = (int) $request->query('department', 0);
        $status = (string) $request->query('status', '');

        $samples = Sample::query()
            ->where('branch_id', $branch->id)
            ->with(['patient', 'items', 'order'])
            ->whereIn('samples.status', ['collected', 'received'])
            ->when($status !== '', fn ($query) => $query->where('samples.status', $status))
            ->when($departmentId > 0, fn ($query) => $query->whereHas(
                'items',
                fn ($items) => $items->where('department_id', $departmentId)->whereNotIn('order_items.status', ['cancelled']),
            ))
            ->orderBy('id')
            ->paginate((int) $request->query('per_page', 25));

        return ApiResponse::success(SampleResource::collection($samples));
    }

    /**
     * Draft results with auto flags, previous values and ranges.
     */
    public function showResults(Branch $branch, OrderItem $item, ResultRows $rows): JsonResponse
    {
        abort_if($this->itemBranchId($item) !== $branch->id, 404);

        return ApiResponse::success([
            'item' => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'status' => $item->status->value,
                'expected_at' => $item->expected_at?->toIso8601String(),
            ],
            'rows' => $rows->forItem($item),
            'narrative' => $item->narrative === null ? null : [
                'findings' => $item->narrative->findings,
                'impression' => $item->narrative->impression,
            ],
        ]);
    }

    public function updateResults(Branch $branch, OrderItem $item, SaveResultsRequest $request, SaveResultsAction $action): JsonResponse
    {
        abort_if($this->itemBranchId($item) !== $branch->id, 404);
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $item = $action->execute($item, $data, $request->user());

        return ApiResponse::success(['id' => $item->id, 'status' => $item->status->value], __('lab.draft_saved'));
    }

    public function acknowledgeCritical(Branch $branch, OrderItem $item, AcknowledgeCriticalRequest $request, AcknowledgeCriticalAction $action): JsonResponse
    {
        abort_if($this->itemBranchId($item) !== $branch->id, 404);
        $item = $action->execute($item, $request->string('informed_name')->toString(), $request->user(), $request->input('informed_at') !== null ? (string) $request->input('informed_at') : null);

        return ApiResponse::success(['id' => $item->id], __('lab.critical_acknowledged'));
    }

    /**
     * Moves the results to verification.
     */
    public function submit(Branch $branch, OrderItem $item, Request $request, SubmitResultsAction $action): JsonResponse
    {
        abort_if($this->itemBranchId($item) !== $branch->id, 404);
        $item = $action->execute($item, $request->user());

        return ApiResponse::success(['id' => $item->id, 'status' => $item->status->value], __('lab.submitted', ['test' => $item->name]));
    }

    private function itemBranchId(OrderItem $item): int
    {
        return (int) data_get($item, 'sample.branch_id', $item->order->branch_id);
    }
}
