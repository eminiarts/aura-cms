    @php
        $status = $row->{$field['slug']};
        $statusOption = collect($field['options'])->firstWhere('key', $status);
    @endphp
    @if($statusOption)
        <span class="text-xs font-medium px-2.5 py-0.5 rounded {{ $statusOption['color'] }}">
            {{ $statusOption['value'] }}
        </span>
    @else
        <span class="text-gray-500 dark:text-gray-400">No status set</span>
    @endif