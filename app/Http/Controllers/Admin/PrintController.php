<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Billing\PrintDocument;
use Illuminate\View\View;

class PrintController extends Controller
{
    public function show(Order $order, PrintDocument $documents): View
    {
        $order->load(['invoice.patient', 'invoice.branch', 'invoice.order.items', 'invoice.payments', 'samples.items', 'samples.patient', 'samples.branch']);
        $invoice = $order->invoice;
        abort_if($invoice === null, 404);

        $locale = in_array($invoice->patient->preferred_locale, ['en', 'bn'], true) ? $invoice->patient->preferred_locale : 'en';
        app()->setLocale($locale);

        return view('prints.bundle', [
            'invoice' => $documents->invoice($invoice),
            'receipts' => $invoice->payments->map(fn ($payment) => $documents->receipt($payment))->all(),
            'labels' => $order->samples->map(fn ($sample) => $documents->label($sample))->all(),
        ]);
    }
}
