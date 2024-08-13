    @php
        $value = $row->{$field['slug']};
        if (isset($field['display_format'])) {
            $value = \Carbon\Carbon::parse($value)->format($field['display_format']);
        }
    @endphp
    <span class="">
        {{ $value }}
    </span>
