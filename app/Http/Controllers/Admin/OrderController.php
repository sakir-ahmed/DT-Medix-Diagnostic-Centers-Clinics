<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Billing\CancelItemsAction;
use App\Actions\Billing\CollectPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelItemsRequest;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $orders = Order::query()
            ->with(['patient', 'invoice'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->whereHas('invoice', fn ($invoice) => $invoice->where('invoice_no', 'like', $search.'%'))
                        ->orWhereHas('patient', fn ($patient) => $patient->where('patient_code', 'like', $search.'%')->orWhere('name', 'like', $search.'%'));
                });
            })
            ->orderByDesc('id')
            ->paginate((int) $request->query('per_page', 25))
            ->withQueryString();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => OrderResource::collection($orders),
            'filters' => ['q' => $search],
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load(['patient', 'invoice.payments', 'items', 'samples']);

        return Inertia::render('Admin/Orders/Show', [
            'order' => (new OrderResource($order))->resolve(),
        ]);
    }

    public function pay(PaymentRequest $request, Order $order, CollectPaymentAction $collect): RedirectResponse
    {
        $invoice = $order->invoice;
        abort_if($invoice === null, 404);

        $userId = $request->user()?->getAuthIdentifier();
        /** @var list<array{amount: string, method?: string}> $lines */
        $lines = $request->validated('lines');
        $collect->execute($invoice, $lines, is_numeric($userId) ? (int) $userId : null);

        return back()->with('success', __('billing.payment_saved'));
    }

    public function cancel(CancelItemsRequest $request, Order $order, CancelItemsAction $cancel): RedirectResponse
    {
        $userId = $request->user()?->getAuthIdentifier();
        /** @var list<int> $itemIds */
        $itemIds = array_map(intval(...), $request->validated('item_ids'));
        $cancel->execute($order, $itemIds, $request->string('reason')->toString(), $request->input('approval_login'), $request->input('approval_pin'), is_numeric($userId) ? (int) $userId : null);

        return back()->with('success', __('billing.items_cancelled'));
    }
}
