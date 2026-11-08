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
        @assets
            @vite(['resources/js/flatpickr.js'], 'vendor/aura/libs')
        @endassets


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
            class="flex relative">
            {{-- The same 36px control as a text input (ring, radius, 14px), with a 16px outline calendar at the end. --}}
            <span
                class="flex absolute inset-y-0 right-0 z-[2] items-center pr-3 text-gray-400 pointer-events-none dark:text-gray-500
                @if ($field['field']->isDisabled($this->form, $field)) opacity-50 @endif
            " aria-hidden="true">
                @if ($type === 'time')
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7.5V12l3 2" />
                    </svg>
                @else
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3.5" y="5" width="17" height="15.5" rx="2.5" />
                        <path d="M3.5 10h17M8 3v4M16 3v4" />
                    </svg>
                @endif
            </span>

            <input
                @if ($live) wire:model.live="form.fields.{{ optional($field)['slug'] }}"
                @else
                placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
                wire:model="form.fields.{{ optional($field)['slug'] }}" @endif
                x-ref="input" @if ($field['field']->isDisabled($this->form, $field)) disabled @endif
                class="block py-1.5 pr-9 pl-3 w-full h-9 text-base text-gray-900 bg-white rounded-lg border-0 ring-1 shadow-xs transition duration-150 appearance-none cursor-pointer sm:text-sm placeholder:text-gray-400 ring-gray-950/10 hover:ring-gray-950/20 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-500 dark:ring-white/10 dark:hover:ring-white/20 dark:disabled:bg-gray-800/50" />
        </div>
    @endif
</x-aura::fields.wrapper>
