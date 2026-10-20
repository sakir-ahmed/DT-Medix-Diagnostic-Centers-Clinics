<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Billing\DecideRefundAction;
use App\Actions\Billing\RequestRefundAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovalDecisionRequest;
use App\Http\Requests\RefundRequest;
use App\Models\Invoice;
use App\Models\Refund;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RefundController extends Controller
{
    public function index(Request $request): Response
    {
        $refunds = Refund::query()
            ->with(['invoice.patient'])
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Refund $refund): array => [
                'id' => $refund->id,
                'invoice_id' => $refund->invoice_id,
                'invoice_no' => $refund->invoice->invoice_no,
                'patient_code' => $refund->invoice->patient->patient_code,
                'patient_name' => $refund->invoice->patient->name,
                'amount' => money($refund->amount),
                'method' => $refund->method,
                'reason' => $refund->reason,
                'status' => $refund->status->value,
                'created_at' => $refund->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Refunds/Index', [
            'refunds' => $refunds,
            'filters' => ['status' => (string) $request->query('status', '')],
        ]);
    }

    public function store(RefundRequest $request, RequestRefundAction $create): RedirectResponse
    {
        $invoice = Invoice::query()->findOrFail($request->integer('invoice_id'));
        $userId = $request->user()?->getAuthIdentifier();
        $create->execute($invoice, $request->string('amount')->toString(), $request->string('reason')->toString(), is_numeric($userId) ? (int) $userId : null);

        return back()->with('success', __('billing.refund_requested'));
    }

    public function decide(ApprovalDecisionRequest $request, Refund $refund, DecideRefundAction $decide): RedirectResponse
    {
        $decide->execute($refund, $request->boolean('approve'), $request->string('approval_login')->toString(), $request->string('approval_pin')->toString());

        return back()->with('success', __('billing.refund_decided'));
    }
}
