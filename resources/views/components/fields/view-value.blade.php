<x-aura::fields.wrapper :field="$field">
    @php
        $value = $this->model->display($field['slug']);
        $instance = $field['field'] ?? null;
        // Single-line values (emails, URLs, phone numbers, slugs, short text) stay on one line and truncate
        // in a narrow details column, with the full value in the tooltip. A Textarea keeps its lines and
        // wraps: it is the one field whose value is a paragraph. Everything else (tags, relations) wraps,
        // with [overflow-wrap:anywhere] breaking a single long token where no space or hyphen is.
        $keepsLines = $instance instanceof \Aura\Base\Fields\Textarea;
        $singleLine = ! $keepsLines && (
            $instance instanceof \Aura\Base\Fields\Text
            || $instance instanceof \Aura\Base\Fields\Email
            || $instance instanceof \Aura\Base\Fields\Phone
            || $instance instanceof \Aura\Base\Fields\Slug
        );
        $tooltip = $singleLine && is_scalar($value) ? trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES)) : null;
    @endphp
    <div @if($tooltip) title="{{ $tooltip }}" @endif class="{{ $keepsLines ? 'whitespace-pre-line' : '' }} {{ $singleLine ? 'truncate' : 'break-words [overflow-wrap:anywhere]' }} min-w-0 text-sm text-gray-900 dark:text-gray-100">
        @if(empty($value))
            <span class="text-gray-400 dark:text-gray-500">–</span>
        @else
            {!! $value !!}
        @endif
    </div>
</x-aura::fields.wrapper>
