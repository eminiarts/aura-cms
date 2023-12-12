<x-aura::fields.wrapper :field="$field">
    {{-- <x-aura::input.date wire:model="post.fields.{{ optional($field)['slug'] }}" error="post.fields.{{ optional($field)['slug'] }}" placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"></x-aura::input.date> --}}


<div
    x-data
    x-init="
        window.flatpickr($refs.input, {
            inline: false,

            dateFormat: '{{ optional($field)['format'] ?? 'd.m.Y' }}',
            altInput: true,
            altFormat: '{{ optional($field)['display_format'] ?? 'd.m.Y' }}',
            enableTime: @js(optional($field)['enable_time']),
            allowInput: true,
            @if (optional($field)['maxDate'])
            minDate: '{{ today()->format( optional($field)['format'] ?? 'd.m.Y' ) }}',
            @endif
            @if (optional($field)['maxDate'])
            maxDate: '{{ now()->addDays( optional($field)['maxDate'] ?? 'false' )->format( optional($field)['format'] ?? 'd.m.Y' ) }}',
            @endif
            'locale': {
                'firstDayOfWeek': {{ optional($field)['weekStartsOn'] ?? '1' }} // start week on Monday
            },
            'disable': []
        });
    "
    @change="$dispatch('input', $event.target.value)"
    wire:ignore
    class="flex rounded-md shadow-sm"
>
    <span class="inline-flex items-center px-3 text-gray-500 border border-r-0 border-gray-500/30 dark:border-gray-700 rounded-l-md bg-gray-50 dark:bg-gray-700 sm:text-sm
        @if ($field['field']->isDisabled($this->post, $field))
        opacity-50 cursor-not-allowed
        @endif
    ">
        <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M6 2C5.44772 2 5 2.44772 5 3V4H4C2.89543 4 2 4.89543 2 6V16C2 17.1046 2.89543 18 4 18H16C17.1046 18 18 17.1046 18 16V6C18 4.89543 17.1046 4 16 4H15V3C15 2.44772 14.5523 2 14 2C13.4477 2 13 2.44772 13 3V4H7V3C7 2.44772 6.55228 2 6 2ZM6 7C5.44772 7 5 7.44772 5 8C5 8.55228 5.44772 9 6 9H14C14.5523 9 15 8.55228 15 8C15 7.44772 14.5523 7 14 7H6Z"/>
        </svg>
    </span>

    <input
        wire:model="post.fields.{{ optional($field)['slug'] }}"
        x-ref="input"
        @if ($field['field']->isDisabled($this->post, $field))
        disabled
        @endif
        class="px-3 py-2 w-full bg-white rounded-none rounded-r-md border appearance-none shadow-xs disabled:opacity-50 disabled:cursor-not-allowed border-gray-500/30 dark:border-gray-700 focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900"
    />
</div>
</x-aura::fields.wrapper>
