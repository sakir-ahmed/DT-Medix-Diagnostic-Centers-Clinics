<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Billing\DecideRefundAction;
use App\Actions\Billing\RequestRefundAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovalDecisionRequest;
use App\Http\Requests\RefundRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Refund;
use Illuminate\Http\JsonResponse;

/**
 * @tags Refunds
 */
class RefundController extends Controller
{
    public function store(Branch $branch, RefundRequest $request, RequestRefundAction $create): JsonResponse
    {
        $invoice = Invoice::query()->findOrFail($request->integer('invoice_id'));
        abort_if($invoice->branch_id !== $branch->id, 404);
        $userId = $request->user()?->getAuthIdentifier();
        $refund = $create->execute($invoice, $request->string('amount')->toString(), $request->string('reason')->toString(), is_numeric($userId) ? (int) $userId : null);

        return ApiResponse::success($this->payload($refund), __('billing.refund_requested'), 201);
    }

    public function decide(Branch $branch, Refund $refund, ApprovalDecisionRequest $request, DecideRefundAction $decide): JsonResponse
    {
        abort_if($refund->branch_id !== $branch->id, 404);
        $refund = $decide->execute($refund, $request->boolean('approve'), $request->string('approval_login')->toString(), $request->string('approval_pin')->toString());

        return ApiResponse::success($this->payload($refund), __('billing.refund_decided'));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Refund $refund): array
    {
        return [
            'id' => $refund->id,
            'invoice_id' => $refund->invoice_id,
            'amount' => money($refund->amount),
            'method' => $refund->method,
            'status' => $refund->status->value,
            'reason' => $refund->reason,
        ];
    }
}
