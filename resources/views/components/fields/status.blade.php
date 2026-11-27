<x-aura::fields.wrapper :field="$field">
    <div x-data="{ open: false, selected: @entangle('form.fields.' . $field['slug']) }" class="relative" wire:ignore>
        <button @click="open = !open" type="button"
                class="flex relative items-center w-full h-9 bg-white dark:bg-gray-800 rounded-lg shadow-xs ring-1 ring-gray-950/10 hover:ring-gray-950/20 dark:ring-white/10 dark:hover:ring-white/20 pl-2 pr-9 text-left text-base cursor-default transition duration-150 focus:outline-none focus:ring-2 focus:ring-primary-500 sm:text-sm">
            <span class="flex items-center">
                <span x-show="selected"
                      :class="selected ? $refs.listbox.querySelector(`[data-value='${selected}']`).getAttribute('data-color') : ''"
                      class="aura-tag aura-tag-status">
                    <svg class="aura-tag-dot" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3" /></svg>
                    <span x-text="selected ? $refs.listbox.querySelector(`[data-value='${selected}']`).textContent.trim() : ''"></span>
                </span>
                <span x-show="!selected" class="pl-1 text-gray-400 dark:text-gray-500">{{ __('Select :field…', ['field' => Str::lower(__($field['name']))]) }}</span>
            </span>
            <span class="flex absolute inset-y-0 right-0 items-center pr-2.5 pointer-events-none">
                <svg class="size-4 text-gray-400 dark:text-gray-500" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M4.25 6.25 8 10l3.75-3.75" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
        </button>

        <div x-show="open" @click.away="open = false"
             class="absolute z-20 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg max-h-60 rounded-lg p-1 text-base ring-1 ring-gray-950/10 dark:ring-white/10 overflow-auto focus:outline-none sm:text-sm"
             x-ref="listbox">
            @foreach ($field['options'] as $option)
                <div @click="selected = '{{ $option['key'] }}'; open = false"
                     class="flex relative items-center pr-9 pl-2 h-8 rounded-md transition-colors cursor-default select-none hover:bg-gray-950/[0.04] dark:hover:bg-white/5"
                     data-value="{{ $option['key'] }}"
                     data-color="{{ $option['color'] }}">
                    <span class="flex items-center">
                        <span class="aura-tag aura-tag-status {{ $option['color'] }}">
                            <svg class="aura-tag-dot" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3" /></svg>
                            {{ $option['value'] }}
                        </span>
                    </span>
                    <span x-show="selected === '{{ $option['key'] }}'"
                          class="flex absolute inset-y-0 right-0 items-center pr-2 text-primary-600 dark:text-primary-400">
                        <svg class="size-4 shrink-0" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path d="m3.5 8.5 3 3 6-7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</x-aura::fields.wrapper>