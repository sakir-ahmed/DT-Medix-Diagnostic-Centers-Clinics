<section class="sheet label">
    {!! $label['barcode'] !!}
    <div><strong>{{ $label['sample_code'] }}</strong></div>
    <div>{{ $label['patient_name'] }}</div>
    <div>{{ $label['age'] }} / {{ $label['sex'] }}</div>
    <div>{{ $label['codes'] }}</div>
    <div>{{ $label['sample_type'] }} · {{ $label['branch_code'] }}</div>
    <div>{{ $label['at']?->timezone('Asia/Dhaka')->format('d M H:i') }}</div>
</section>
