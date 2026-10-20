<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Billing\CancelItemsAction;
use App\Actions\Billing\CreateOrderAction;
use App\Actions\Billing\DecideDiscountAction;
use App\Actions\Billing\RequestDiscountAction;
use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovalDecisionRequest;
use App\Http\Requests\CancelItemsRequest;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\StoreDiscountRequest;
use App\Http\Resources\OrderResource;
use App\Http\Responses\ApiResponse;
use App\Models\Branch;
use App\Models\DiscountRequest;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

/**
 * @tags Orders
 */
class OrderController extends Controller
{
    /**
     * Create an order and its invoice. Requires an Idempotency-Key.
     */
    public function store(Branch $branch, OrderRequest $request, CreateOrderAction $create): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $order = $create->execute($branch, $data, $request->user());

        return ApiResponse::success(new OrderResource($order), __('billing.order_saved', ['invoice' => $order->invoice?->invoice_no]), 201);
    }

    public function show(Branch $branch, Order $order): JsonResponse
    {
        abort_if($order->branch_id !== $branch->id, 404);
        $order->load(['patient', 'invoice.payments', 'items', 'samples']);

        return ApiResponse::success(new OrderResource($order));
    }

    public function cancel(Branch $branch, Order $order, CancelItemsRequest $request, CancelItemsAction $cancel): JsonResponse
    {
        abort_if($order->branch_id !== $branch->id, 404);
        $userId = $request->user()?->getAuthIdentifier();
        /** @var list<int> $itemIds */
        $itemIds = array_map(intval(...), $request->validated('item_ids'));
        $order = $cancel->execute($order, $itemIds, $request->string('reason')->toString(), $request->input('approval_login'), $request->input('approval_pin'), is_numeric($userId) ? (int) $userId : null);

        return ApiResponse::success(new OrderResource($order), __('billing.items_cancelled'));
    }

    public function storeDiscount(Branch $branch, Order $order, StoreDiscountRequest $request, RequestDiscountAction $discounts): JsonResponse
    {
        abort_if($order->branch_id !== $branch->id || $order->invoice === null, 404);

        $discount = $discounts->execute(
            $order->invoice,
            $request->input('discount_type'),
            $request->input('discount_value') === null ? null : (string) $request->input('discount_value'),
            $request->string('discount_category')->toString() ?: 'standard',
            $request->input('discount_reason'),
            $request->user(),
            $request->boolean('insist_discount'),
            $request->input('approval_login'),
            $request->input('approval_pin'),
        );

        return ApiResponse::success([
            'id' => $discount->id,
            'status' => $discount->status->value,
            'invoice_id' => $discount->invoice_id,
        ], __('billing.discount_decided'), 201);
    }

    public function decideDiscount(Branch $branch, DiscountRequest $discountRequest, ApprovalDecisionRequest $request, DecideDiscountAction $decide): JsonResponse
    {
        abort_if($discountRequest->branch_id !== $branch->id, 404);
        $discountRequest = $decide->execute($discountRequest, $request->boolean('approve'), $request->string('approval_login')->toString(), $request->string('approval_pin')->toString());

        return ApiResponse::success([
            'id' => $discountRequest->id,
            'status' => $discountRequest->status instanceof ApprovalStatus ? $discountRequest->status->value : $discountRequest->status,
            'invoice_id' => $discountRequest->invoice_id,
        ], __('billing.discount_decided'));
    }
}
