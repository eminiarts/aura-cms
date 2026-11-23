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
            {{-- Same pill as the Status field (status-index), so a colored option reads the same everywhere. --}}
            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium whitespace-nowrap rounded-full ring-1 ring-inset ring-gray-950/10 dark:ring-white/10 {{ $colors[$item] ?? 'bg-gray-50 text-gray-600 dark:bg-white/5 dark:text-gray-300' }}">
                {{ __($labels[$item] ?? $item) }}
            </span>
        @endforeach
    </div>
@endif
