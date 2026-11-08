@php
    if (is_string($value) && str_starts_with($value, '[')) {
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $value = $decoded;
        }
    }

    $values = is_array($value) ? $value : ((is_null($value) || $value === '') ? [] : [$value]);

    $options = collect(app('Aura\Base\Fields\Select')->options($row, $field));

    // Options are either a repeater list of ['key' => ..., 'value' => ..., 'color' => ...] or a plain key => label map.
    $isList = $options->isNotEmpty() && is_array($options->first());
    $labels = $isList ? $options->pluck('value', 'key') : $options;
    $colors = $isList ? $options->pluck('color', 'key') : collect();
@endphp

@if (count($values))
    <div class="flex flex-wrap gap-1">
        @foreach ($values as $item)
            @if (! empty($colors[$item]))
                <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-md whitespace-nowrap {{ $colors[$item] }}">
                    {{ __($labels[$item] ?? $item) }}
                </span>
            @else
                <span class="aura-tag">{{ __($labels[$item] ?? $item) }}</span>
            @endif
        @endforeach
    </div>
@endif
