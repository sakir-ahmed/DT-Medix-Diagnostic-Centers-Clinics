<section class="sheet" @if (($format ?? 'a5') === 'thermal') style="width:72mm" @endif>
    <h1>{{ $invoice['center'] }}</h1>
    <p class="muted">
        {{ $invoice['branch_name'] }}
        @if ($invoice['address'])<br>{{ $invoice['address'] }}@endif
        @if ($invoice['phone'])<br>{{ $invoice['phone'] }}@endif
        @if ($invoice['license'])<br>{{ __('billing.license') }}: {{ $invoice['license'] }}@endif
    </p>
    <p>
        <strong>{{ $invoice['invoice_no'] }}</strong><br>
        {{ $invoice['issued_at']?->timezone('Asia/Dhaka')->format('d M Y, h:i A') }}
    </p>
    <p>
        {{ $invoice['patient_name'] }}<br>
        {{ $invoice['patient_code'] }} · {{ $invoice['age'] }} / {{ $invoice['sex'] }}
        @if ($invoice['mobile'])<br>{{ $invoice['mobile'] }}@endif
    </p>
    <table>
        <thead>
            <tr>
                <th>{{ __('billing.code') }}</th>
                <th>{{ __('billing.test') }}</th>
                <th class="num">{{ __('billing.price') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice['items'] as $item)
                <tr>
                    <td>{{ $item['code'] }}</td>
                    <td>
                        {{ $item['name'] }}
                        @if ($item['expected_at'])
                            <div class="muted">{{ __('billing.expected') }}: {{ $item['expected_at']->timezone('Asia/Dhaka')->format('d M, h:i A') }}</div>
                        @endif
                    </td>
                    <td class="num">{{ $item['price'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table>
        <tr><td>{{ __('billing.subtotal') }}</td><td class="num">{{ $invoice['subtotal'] }}</td></tr>
        @if ($invoice['discount'] !== '0.00')
            <tr><td>{{ __('billing.discount') }}</td><td class="num">{{ $invoice['discount'] }}</td></tr>
        @endif
        @if ($invoice['vat_enabled'])
            <tr><td>{{ __('billing.vat') }}</td><td class="num">{{ $invoice['vat'] }}</td></tr>
        @endif
        @if ($invoice['service_enabled'])
            <tr><td>{{ __('billing.service_charge') }}</td><td class="num">{{ $invoice['service'] }}</td></tr>
        @endif
        <tr><td><strong>{{ __('billing.total') }}</strong></td><td class="num"><strong>{{ $invoice['total'] }}</strong></td></tr>
        <tr><td>{{ __('billing.paid') }}</td><td class="num">{{ $invoice['paid'] }}</td></tr>
        <tr><td>{{ __('billing.due') }}</td><td class="num">{{ $invoice['due'] }}</td></tr>
        @if ($invoice['method'] !== '')
            <tr><td>{{ __('billing.method') }}</td><td class="num">{{ __('payments.methods.'.$invoice['method']) }}</td></tr>
        @endif
    </table>
</section>
