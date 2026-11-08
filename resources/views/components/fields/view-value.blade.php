<x-aura::fields.wrapper :field="$field">
    @php
        $value = $this->model->display($field['slug']);
        // One line per value; a value cut off in a narrow details card shows in full on hover.
        $fullText = is_scalar($value) ? trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES)) : '';
    @endphp
    <div class="truncate text-sm text-gray-900 dark:text-gray-100" @if ($fullText !== '') title="{{ $fullText }}" @endif>
        @if(empty($value))
            <span class="text-gray-400 dark:text-gray-500">–</span>
        @else
            {!! $value !!}
        @endif
    </div>
</x-aura::fields.wrapper>
