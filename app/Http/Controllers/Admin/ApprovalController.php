<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Billing\DecideDiscountAction;
use App\Enums\ApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovalDecisionRequest;
use App\Models\DiscountRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    public function index(): Response
    {
        $requests = DiscountRequest::query()
            ->with(['invoice.patient', 'invoice.order'])
            ->where('status', ApprovalStatus::Pending)
            ->orderBy('id')
            ->get()
            ->map(fn (DiscountRequest $request): array => [
                'id' => $request->id,
                'invoice_id' => $request->invoice_id,
                'invoice_no' => $request->invoice->invoice_no,
                'order_id' => $request->invoice->order_id,
                'patient_code' => $request->invoice->patient->patient_code,
                'patient_name' => $request->invoice->patient->name,
                'discount_type' => $request->discount_type,
                'discount_value' => money($request->discount_value),
                'reason' => $request->reason,
                'status' => $request->status->value,
                'created_at' => $request->created_at?->toIso8601String(),
            ])->values();

        return Inertia::render('Admin/Approvals/Index', [
            'requests' => $requests,
        ]);
    }

    public function decide(ApprovalDecisionRequest $request, DiscountRequest $discountRequest, DecideDiscountAction $decide): RedirectResponse
    {
        $decide->execute($discountRequest, $request->boolean('approve'), $request->string('approval_login')->toString(), $request->string('approval_pin')->toString());

        return back()->with('success', __('billing.discount_decided'));
    }
}
