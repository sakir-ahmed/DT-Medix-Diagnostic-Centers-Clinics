<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Sample;
use Picqer\Barcode\BarcodeGeneratorSVG;

/**
 * View data for invoices, money receipts and sample labels.
 * The referrer is omitted from the patient copy. There is no portal QR.
 */
final class PrintDocument
{
    /**
     * @return array<string, mixed>
     */
    public function invoice(Invoice $invoice): array
    {
        $invoice->loadMissing(['patient', 'branch', 'order.items', 'payments']);
        $patient = $invoice->patient;
        $branch = $invoice->branch;
        $tenant = tenant();

        return [
            'center' => $this->centerName(),
            'branch_name' => $branch?->name,
            'address' => $branch?->address,
            'phone' => $branch?->phone,
            'license' => $branch?->license_no,
            'invoice_no' => $invoice->invoice_no,
            'issued_at' => $invoice->created_at,
            'patient_name' => $patient->preferred_locale === 'bn' && filled($patient->name_bn) ? $patient->name_bn : $patient->name,
            'patient_code' => $patient->patient_code,
            'age' => $patient->ageText(),
            'sex' => __('patients.sex_short.'.$patient->sex->value),
            'mobile' => $patient->mobileDisplay(),
            'items' => $invoice->order->items->map(fn (OrderItem $item): array => [
                'code' => $item->code,
                'name' => $item->name,
                'price' => money($item->price),
                'expected_at' => $item->expected_at,
                'status' => $item->status->value,
            ])->all(),
            'subtotal' => money($invoice->subtotal),
            'discount' => money($invoice->discount_amount),
            'vat' => money($invoice->vat_amount),
            'service' => money($invoice->service_charge),
            'vat_enabled' => (bool) $tenant?->setting('vat_enabled'),
            'service_enabled' => (bool) $tenant?->setting('service_charge_enabled'),
            'total' => money($invoice->total),
            'paid' => money($invoice->paid),
            'due' => money($invoice->due),
            'method' => $invoice->payments->pluck('method')->map(fn ($method) => $method->value)->unique()->implode(', '),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function receipt(Payment $payment): array
    {
        $payment->loadMissing(['invoice.patient', 'invoice.branch']);

        return [
            'center' => $this->centerName(),
            'branch_name' => $payment->invoice->branch?->name,
            'receipt_no' => $payment->receipt_no,
            'invoice_no' => $payment->invoice->invoice_no,
            'paid_at' => $payment->paid_at,
            'patient_name' => $payment->invoice->patient->name,
            'patient_code' => $payment->invoice->patient->patient_code,
            'amount' => money($payment->amount),
            'method' => $payment->method->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function label(Sample $sample): array
    {
        $sample->loadMissing(['patient', 'items', 'branch']);
        $patient = $sample->patient;
        $name = $patient->preferred_locale === 'bn' && filled($patient->name_bn) ? $patient->name_bn : $patient->name;

        return [
            'barcode' => (new BarcodeGeneratorSVG)->getBarcode($sample->sample_code, BarcodeGeneratorSVG::TYPE_CODE_128, 1, 36),
            'sample_code' => $sample->sample_code,
            'patient_name' => mb_substr($name, 0, 22),
            'age' => $patient->ageText(),
            'sex' => __('patients.sex_short.'.$patient->sex->value),
            'codes' => $sample->items->pluck('code')->implode(', '),
            'sample_type' => $sample->sample_type,
            'branch_code' => $sample->branch?->code,
            'at' => $sample->created_at,
        ];
    }

    private function centerName(): string
    {
        $tenant = tenant();

        if ($tenant === null) {
            return (string) config('app.name');
        }

        return app()->getLocale() === 'bn' && filled($tenant->name_bn) ? $tenant->name_bn : $tenant->name;
    }
}
