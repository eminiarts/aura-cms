@if($value === null || $value === '')
    <span class="text-gray-400">–</span>
@else
    @php
        if (isset($field['display_format'])) {
            $value = \Carbon\Carbon::parse($value)->format($field['display_format']);
        }
    @endphp
    <span>{{ $value }}</span>
@endif
