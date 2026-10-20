<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice['invoice_no'] }}</title>
    <style>
        body { font-family: "Nirmala UI", "Noto Sans Bengali", sans-serif; color: #111; margin: 0; }
        .sheet { page-break-after: always; padding: 12px; }
        .sheet:last-child { page-break-after: auto; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { text-align: left; padding: 3px 0; vertical-align: top; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .muted { color: #444; }
        .label { width: 48mm; }
        .label svg { max-width: 46mm; height: auto; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <p class="no-print"><button type="button" onclick="window.print()">{{ __('billing.print') }}</button></p>
    @include('prints.invoice', ['invoice' => $invoice, 'format' => 'a5'])
    @foreach ($receipts as $receipt)
        @include('prints.receipt', ['receipt' => $receipt])
    @endforeach
    @foreach ($labels as $label)
        @include('prints.label', ['label' => $label])
    @endforeach
</body>
</html>
