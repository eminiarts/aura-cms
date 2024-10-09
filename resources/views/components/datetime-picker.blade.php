<!-- resources/views/components/datetime-picker.blade.php -->
@props([
    'field',
    'type' => 'date', // 'date', 'datetime', or 'time'
    'enableTime' => false,
    'enableSeconds' => false,
    'noCalendar' => false,
    'format' => 'd.m.Y',
    'displayFormat' => 'd.m.Y',
    'time24hr' => false,
    'minTime' => null,
    'maxTime' => null,
    'maxDate' => null,
    'minDate' => null,
    'weekStartsOn' => 1,
    'enableInput' => true,
    'native' => false,
    'live' => false,
])


<x-aura::fields.wrapper :field="$field">
    @if ($native)
        @if ($live)
            <x-aura::input :type="$type" wire:model.live="form.fields.{{ optional($field)['slug'] }}"
                error="form.fields.{{ optional($field)['slug'] }}"
                placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
                autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"></x-aura::input>
        @else
            <x-aura::input :type="$type" wire:model="form.fields.{{ optional($field)['slug'] }}"
                error="form.fields.{{ optional($field)['slug'] }}"
                placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
                autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"></x-aura::input>
        @endif
    @else
                    @once
                        @push('scripts')
                                @vite(['resources/js/flatpickr.js'], 'vendor/aura/libs')
                        @endpush
                    @endonce


        <div x-data x-init="
        window.flatpickr($refs.input, {
            inline: false,
            @if (isset($this->form['fields'][$field['slug']]) &&
                    !is_null($this->form['fields'][$field['slug']]) &&
                    $this->form['fields'][$field['slug']] !== '' &&
                    $this->form['fields'][$field['slug']] !== 0 &&
                    $this->form['fields'][$field['slug']] !== '0') defaultDate: '{{ $this->form['fields'][$field['slug']] }}',
                    @else
                    defaultDate: false, @endif
            dateFormat: '{{ $format }}',
            altInput: true,
            altFormat: '{{ $displayFormat }}',
            enableTime: {{ $enableTime ? 'true' : 'false' }},
            noCalendar: {{ $noCalendar ? 'true' : 'false' }},
            time_24hr: {{ $time24hr ? 'true' : 'false' }},
            allowInput: {{ json_encode($enableInput) }},
            @if ($minDate) minDate: '{{ today()->format($format) }}', @endif
            @if ($maxDate) maxDate: '{{ now()->addDays($maxDate)->format($format) }}', @endif
            @if ($minTime) minTime: '{{ $minTime }}', @endif
            @if ($maxTime) maxTime: '{{ $maxTime }}', @endif 'locale': {
                'firstDayOfWeek': {{ $weekStartsOn }} // start week on Monday
            },
            'disable': []
        });" @change="$dispatch('input', { value: $event.target.value })" wire:ignore
            class="flex relative rounded-md shadow-sm">
            <span
                class="pointer-events-none h-full absolute right-0 inline-flex items-center px-3 text-gray-700 dark:text-gray-400 sm:text-sm
                @if ($field['field']->isDisabled($this->form, $field)) opacity-50 cursor-not-allowed @endif
            ">
                <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M6 2C5.44772 2 5 2.44772 5 3V4H4C2.89543 4 2 4.89543 2 6V16C2 17.1046 2.89543 18 4 18H16C17.1046 18 18 17.1046 18 16V6C18 4.89543 17.1046 4 16 4H15V3C15 2.44772 14.5523 2 14 2C13.4477 2 13 2.44772 13 3V4H7V3C7 2.44772 6.55228 2 6 2ZM6 7C5.44772 7 5 7.44772 5 8C5 8.55228 5.44772 9 6 9H14C14.5523 9 15 8.55228 15 8C15 7.44772 14.5523 7 14 7H6Z" />
                </svg>
            </span>

            <input
                @if ($live) wire:model.live="form.fields.{{ optional($field)['slug'] }}"
                @else
                placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
                wire:model="form.fields.{{ optional($field)['slug'] }}" @endif
                x-ref="input" @if ($field['field']->isDisabled($this->form, $field)) disabled @endif
                class="px-3 py-2 w-full bg-white rounded-md border appearance-none cursor-pointer hover:bg-gray-50 shadow-xs disabled:opacity-50 disabled:cursor-not-allowed border-gray-500/30 dark:border-gray-700 focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900" />
        </div>
    @endif
</x-aura::fields.wrapper>
