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
            <x-aura::input.indicator :icon="$type === 'time' ? 'clock' : 'calendar'" />

            <input
                @if ($live) wire:model.live="form.fields.{{ optional($field)['slug'] }}"
                @else
                placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
                wire:model="form.fields.{{ optional($field)['slug'] }}" @endif
                x-ref="input" @if ($field['field']->isDisabled($this->form, $field)) disabled @endif
                class="aura-field aura-control-base pl-3 pr-9 w-full text-sm appearance-none cursor-pointer" />
        </div>
    @endif
</x-aura::fields.wrapper>
