<x-aura::fields.wrapper :field="$field">
    <div x-data="{ open: false, selected: @entangle('form.fields.' . $field['slug']) }" class="relative" wire:ignore>
        <button @click="open = !open" type="button"
                class="relative w-full bg-white dark:bg-gray-800 rounded-lg shadow-sm ring-1 ring-inset ring-gray-950/10 dark:ring-white/10 pl-3 pr-10 py-2 text-left cursor-default transition-shadow focus:outline-none focus:ring-2 focus:ring-primary-500 sm:text-sm">
            <span class="flex items-center">
                <span x-show="selected"
                      :class="selected ? $refs.listbox.querySelector(`[data-value='${selected}']`).getAttribute('data-color') : ''"
                      class="inline-flex items-center gap-x-1.5 whitespace-nowrap rounded-full me-2 px-2.5 py-1 text-xs font-medium ring-1 ring-inset ring-gray-950/10 dark:ring-white/10">
                    <svg class="size-1.5 shrink-0 fill-current opacity-70" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3" /></svg>
                    <span x-text="selected ? $refs.listbox.querySelector(`[data-value='${selected}']`).textContent.trim() : ''"></span>
                </span>
                <span x-show="!selected" class="text-gray-500 dark:text-gray-400">{{ __('Select :field...', ['field' => __($field['name'])]) }}</span>
            </span>
            <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </span>
        </button>

        <div x-show="open" @click.away="open = false"
             class="absolute z-10 mt-2 w-full bg-white dark:bg-gray-800 shadow-lg max-h-60 rounded-lg p-1 text-base ring-1 ring-gray-950/5 dark:ring-white/10 overflow-auto focus:outline-none sm:text-sm"
             x-ref="listbox">
            @foreach ($field['options'] as $option)
                <div @click="selected = '{{ $option['key'] }}'; open = false"
                     class="cursor-default select-none relative rounded-md py-2 pl-2 pr-9 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
                     data-value="{{ $option['key'] }}"
                     data-color="{{ $option['color'] }}">
                    <span class="flex items-center">
                        <span class="inline-flex items-center gap-x-1.5 whitespace-nowrap rounded-full me-2 px-2.5 py-1 text-xs font-medium ring-1 ring-inset ring-gray-950/10 dark:ring-white/10 {{ $option['color'] }}">
                            <svg class="size-1.5 shrink-0 fill-current opacity-70" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3" /></svg>
                            {{ $option['value'] }}
                        </span>
                    </span>
                    <span x-show="selected === '{{ $option['key'] }}'"
                          class="absolute inset-y-0 right-0 flex items-center pr-3 text-primary-600 dark:text-primary-400">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</x-aura::fields.wrapper>