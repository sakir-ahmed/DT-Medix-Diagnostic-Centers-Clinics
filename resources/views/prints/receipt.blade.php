<section class="sheet" style="width:72mm">
    <h1>{{ $receipt['center'] }}</h1>
    <p class="muted">{{ $receipt['branch_name'] }}</p>
    <p><strong>{{ __('billing.money_receipt') }} {{ $receipt['receipt_no'] }}</strong></p>
    <p>
        {{ $receipt['patient_name'] }}<br>
        {{ $receipt['patient_code'] }}<br>
        {{ __('billing.invoice') }} {{ $receipt['invoice_no'] }}<br>
        {{ $receipt['paid_at']?->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}
    </p>
    <p><strong>{{ __('billing.amount') }}: {{ $receipt['amount'] }}</strong></p>
    <p>{{ __('billing.method') }}: {{ __('payments.methods.'.$receipt['method']) }}</p>
</section>
