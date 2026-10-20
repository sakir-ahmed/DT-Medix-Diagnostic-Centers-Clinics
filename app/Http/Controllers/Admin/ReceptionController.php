<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Billing\CreateOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderRequest;
use App\Models\Branch;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Referrer;
use App\Support\BranchContext;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReceptionController extends Controller
{
    public function index(): Response
    {
        $center = center();

        return Inertia::render('Admin/Reception/Index', [
            'doctors' => Doctor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])->map(fn (Doctor $doctor): array => [
                'value' => $doctor->id,
                'label' => $doctor->name,
            ])->values(),
            'referrers' => Referrer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])->map(fn (Referrer $referrer): array => [
                'value' => $referrer->id,
                'label' => $referrer->name,
            ])->values(),
            'discount_limit_percent' => (int) ($center?->setting('discount_limit_percent') ?? 10),
            'vat_enabled' => (bool) $center?->setting('vat_enabled'),
            'vat_percent' => (string) ($center?->setting('vat_percent') ?? 0),
            'service_charge_enabled' => (bool) $center?->setting('service_charge_enabled'),
            'service_charge_percent' => (string) ($center?->setting('service_charge_percent') ?? 0),
            'bloodGroups' => Patient::BLOOD_GROUPS,
        ]);
    }

    public function store(OrderRequest $request, CreateOrderAction $create, BranchContext $context): RedirectResponse
    {
        $branch = $this->branch($context);
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $order = $create->execute($branch, $data, $request->user());

        return redirect()->route('admin.reception.index')->with('success', __('billing.order_saved', ['invoice' => $order->invoice?->invoice_no]))->with('print_order_id', $order->id);
    }

    private function branch(BranchContext $context): Branch
    {
        $branch = Branch::query()->whereKey($context->currentBranchId())->where('is_active', true)->first();
        abort_if($branch === null, 404);

        return $branch;
    }
}
