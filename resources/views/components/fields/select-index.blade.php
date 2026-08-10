@php
    $rawValue = $row->{$field['slug']};

    if (is_string($rawValue) && str_starts_with($rawValue, '[')) {
        $decoded = json_decode($rawValue, true);
        if (is_array($decoded)) {
            $rawValue = $decoded;
        }
    }

    $rawValues = is_array($rawValue) ? array_values($rawValue) : [$rawValue];
    $values = is_array($value) ? $value : ((is_null($value) || $value === '') ? [] : [$value]);

    $options = collect(app('Aura\Base\Fields\Select')->options($row, $field));

    // Options are either a repeater list of ['key' => ..., 'value' => ..., 'color' => ...] or a plain key => label map.
    $isList = $options->isNotEmpty() && is_array($options->first());
@endphp

@if (count($values))
    <div class="flex flex-wrap gap-1">
        @foreach ($values as $index => $item)
            @php
                $rawItem = $rawValues[$index] ?? null;
                $option = $isList
                    ? $options->first(fn (mixed $candidate): bool => array_key_exists('key', $candidate) && $candidate['key'] === $rawItem)
                    : null;
                $color = $option['color'] ?? 'bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10';
            @endphp
            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md whitespace-nowrap {{ $color }}">
                {{ __((string) $item) }}
            </span>
        @endforeach
    </div>
@endif
