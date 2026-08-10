<div class="truncate">
        @php
        $rawStatus = $row->{$field['slug']};
        $statusOption = collect($field['options'])->first(
            fn (mixed $option): bool => is_array($option)
                && array_key_exists('key', $option)
                && $option['key'] === $rawStatus,
        );
    @endphp
    @if($statusOption)
        <span class="inline-flex items-center gap-x-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset ring-gray-950/10 dark:ring-white/10 {{ $statusOption['color'] }}">
            <svg class="size-1.5 shrink-0 fill-current opacity-70" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3" /></svg>
            {{ $value }}
        </span>
    @else
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $value }}</span>
    @endif
</div>
