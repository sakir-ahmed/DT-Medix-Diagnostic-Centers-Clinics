<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Enums\PaymentMethod;
use App\Exceptions\DomainException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\DocumentNumbers;
use App\Services\Billing\InvoiceCalculator;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Records one or more cash lines against an invoice. The due is never
 * typed: a line that would take the paid amount past the total is refused.
 */
final class CollectPaymentAction
{
    public function __construct(
        private readonly InvoiceCalculator $calculator,
        private readonly DocumentNumbers $numbers,
    ) {}

    /**
     * @param  list<array{amount: string, method?: string}>  $lines
     */
    public function execute(Invoice $invoice, array $lines, ?int $receivedBy = null): Invoice
    {
        if ($lines === []) {
            return $invoice;
        }

        return DB::transaction(function () use ($invoice, $lines, $receivedBy): Invoice {
            /** @var Invoice $invoice */
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $invoice = $this->calculator->recalculate($invoice);

            foreach ($lines as $line) {
                if (($line['method'] ?? PaymentMethod::Cash->value) !== PaymentMethod::Cash->value) {
                    throw DomainException::make('INVALID_STATE', 422);
                }
            }

            $sum = Money::add(...array_map(fn (array $line): string => Money::of($line['amount']), $lines));

            if (Money::isZero((string) $invoice->due)) {
                throw DomainException::make('INVOICE_ALREADY_PAID', 409);
            }

            if (Money::compare($sum, (string) $invoice->due) > 0 || Money::compare($sum, '0') <= 0) {
                throw DomainException::make('PAYMENT_EXCEEDS_DUE', 422);
            }

            $branch = $invoice->branch;

            foreach ($lines as $line) {
                $amount = Money::of($line['amount']);

                if (Money::compare($amount, '0') <= 0) {
                    throw DomainException::make('PAYMENT_EXCEEDS_DUE', 422);
                }

                Payment::query()->create([
                    'branch_id' => $invoice->branch_id,
                    'invoice_id' => $invoice->id,
                    'patient_id' => $invoice->patient_id,
                    'receipt_no' => $this->numbers->receipt($branch),
                    'method' => PaymentMethod::Cash,
                    'amount' => $amount,
                    'status' => 'completed',
                    'received_by' => $receivedBy,
                    'paid_at' => now(),
                ]);
            }

            return $this->calculator->recalculate($invoice);
        });
    }
}
