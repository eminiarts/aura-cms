<div class="truncate">
        @php
        $status = $row->{$field['slug']};
        $statusOption = collect($field['options'])->firstWhere('key', $status);
    @endphp
    @if($statusOption)
        <span class="aura-tag aura-tag-status {{ $statusOption['color'] }}">
            <svg class="aura-tag-dot" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3" /></svg>
            {{ $statusOption['value'] }}
        </span>
    @else
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $status }}</span>
    @endif
</div>