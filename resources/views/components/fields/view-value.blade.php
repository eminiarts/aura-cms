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
        // A boolean reads as a word on the record page: a small check and "Yes", or a muted "No". Lists keep
        // Boolean::display()'s icons; a field with its own display callback keeps that.
        $isBoolean = $instance instanceof \Aura\Base\Fields\Boolean && ! isset($field['display']);
    @endphp
    @if ($isBoolean)
        @if ($this->model->{$field['slug']})
            <div class="flex gap-1.5 items-center text-sm text-gray-900 dark:text-gray-100" data-boolean-value="true">
                <svg class="size-4 shrink-0 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 8.5 6.5 11.5 12.5 4.5" /></svg>
                {{ __('Yes') }}
            </div>
        @else
            <div class="text-sm text-gray-500 dark:text-gray-400" data-boolean-value="false">{{ __('No') }}</div>
        @endif
    @else
    {{-- One line on purpose: whitespace-pre-line would render the template's own line breaks as a blank first line. --}}
    <div @if($tooltip) title="{{ $tooltip }}" @endif class="{{ $keepsLines ? 'whitespace-pre-line' : '' }} {{ $singleLine ? 'truncate' : 'break-words [overflow-wrap:anywhere]' }} min-w-0 text-sm text-gray-900 dark:text-gray-100">@if(empty($value))<span class="text-gray-400 dark:text-gray-500">–</span>@else{!! $keepsLines ? trim((string) $value) : $value !!}@endif</div>
    @endif
</x-aura::fields.wrapper>
