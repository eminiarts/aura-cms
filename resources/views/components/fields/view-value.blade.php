<x-aura::fields.wrapper :field="$field">
    @php
        $value = $this->model->display($field['slug']);
        // Values wrap instead of truncating, so a long email or URL in a narrow details column reads in full;
        // [overflow-wrap:anywhere] breaks a single long token where no space or hyphen is.
        // A Textarea keeps its lines: it is the one field whose value is a paragraph.
        $keepsLines = ($field['field'] ?? null) instanceof \Aura\Base\Fields\Textarea;
    @endphp
    <div class="{{ $keepsLines ? 'whitespace-pre-line' : '' }} min-w-0 break-words [overflow-wrap:anywhere] text-sm text-gray-900 dark:text-gray-100">
        @if(empty($value))
            <span class="text-gray-400 dark:text-gray-500">–</span>
        @else
            {!! $value !!}
        @endif
    </div>
</x-aura::fields.wrapper>
