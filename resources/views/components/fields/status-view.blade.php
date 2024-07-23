<x-aura::fields.wrapper :field="$field">
    @php
        $status = $this->model->{$field['slug']};
        $statusOption = collect($field['options'])->firstWhere('key', $status);
    @endphp
    @if($statusOption)
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusOption['color'] }}">
            {{ $statusOption['value'] }}
        </span>
    @else
        <span class="text-gray-500 dark:text-gray-400">No status set</span>
    @endif
</x-aura::fields.wrapper>