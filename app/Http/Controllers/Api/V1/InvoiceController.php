<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Billing\CollectPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Branch;
use App\Models\Invoice;
use App\Services\Billing\PrintDocument;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * @tags Invoices
 */
class InvoiceController extends Controller
{
    /**
     * Record one or more cash lines. Requires an Idempotency-Key.
     */
    public function pay(Branch $branch, Invoice $invoice, PaymentRequest $request, CollectPaymentAction $collect): JsonResponse
    {
        abort_if($invoice->branch_id !== $branch->id, 404);
        $userId = $request->user()?->getAuthIdentifier();
        /** @var list<array{amount: string, method?: string}> $lines */
        $lines = $request->validated('lines');
        $invoice = $collect->execute($invoice, $lines, is_numeric($userId) ? (int) $userId : null);

        return ApiResponse::success(new InvoiceResource($invoice->load('payments')), __('billing.payment_saved'));
    }

    /**
     * Invoice PDF. format=a5 or format=thermal. No portal QR.
     */
    public function print(Branch $branch, Invoice $invoice, PrintDocument $documents): Responsable
    {
        abort_if($invoice->branch_id !== $branch->id, 404);
        $format = request()->query('format', 'a5') === 'thermal' ? 'thermal' : 'a5';
        $locale = in_array($invoice->patient->preferred_locale, ['en', 'bn'], true) ? $invoice->patient->preferred_locale : 'en';
        app()->setLocale($locale);

        $pdf = Pdf::view('prints.invoice', ['invoice' => $documents->invoice($invoice), 'format' => $format])->name($invoice->invoice_no.'.pdf');

        if ($format === 'thermal') {
            $pdf->paperSize(80, 200, 'mm');
        } else {
            $pdf->format('a5');
        }

        return $pdf->inline($invoice->invoice_no.'.pdf');
    }
}
