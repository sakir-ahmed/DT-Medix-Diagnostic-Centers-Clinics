<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $label['sample_code'] }}</title>
    <style>
        body { font-family: "Nirmala UI", "Noto Sans Bengali", sans-serif; color: #111; margin: 0; }
        .sheet { padding: 12px; }
        .label { width: 48mm; }
        .label svg { max-width: 46mm; height: auto; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <p class="no-print"><button type="button" onclick="window.print()">{{ __('billing.print') }}</button></p>
    @include('prints.label', ['label' => $label])
</body>
</html>
