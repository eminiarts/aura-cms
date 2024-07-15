<x-aura::fields.wrapper :field="$field">
    <div class="relative">
        <select @if ($disabled = $field['field']->isDisabled($this->form, $field)) disabled @endif
            @if (optional($field)['live'] === true) wire:model.live="form.fields.{{ optional($field)['slug'] }}"
  @else
  wire:model="form.fields.{{ optional($field)['slug'] }}" @endif
            id="aura_field_{{ optional($field)['slug'] }}" error="form.fields.{{ optional($field)['slug'] }}"
            name="post_fields_{{ optional($field)['slug'] }}" id="post_fields_{{ optional($field)['slug'] }}"
            class="block px-3 py-2 pr-10 pl-3 mt-1 w-full text-base bg-white rounded-lg appearance-none shadow-xs border-gray-500/30 focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700 sm:text-sm disabled:cursor-not-allowed disabled:opacity-75 disabled:bg-gray-100 disabled:dark:bg-gray-800">

            @if (optional($field)['placeholder'])
                <option value="">{{ optional($field)['placeholder'] }}</option>
            @else
                <option value="">Select {{ optional($field)['name'] }}...</option>
            @endif

            @php
                $optionGroup = false;
                $options = optional($field)['options'];

                if (isset($this->model)) {
                    $options = $field['field']->options($this->model, $field);
                }
            @endphp

            @foreach ($options as $key => $value)
                @if (is_array($value) && is_string($key))
                    {{-- This is a grouped option --}}
                    <optgroup label="{{ $key }}">
                        @foreach ($value as $optionValue => $optionLabel)
                            <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                        @endforeach
                    </optgroup>
                @elseif (is_array($value))
                    @if (isset($value['key']) && isset($value['value']))
                        {{-- This is a flat option --}}
                        <option value="{{ $value['key'] }}">{{ $value['value'] }}</option>
                    @endif
                @else
                    {{-- This is a flat option --}}
                    <option value="{{ $key }}">{{ $value }}</option>
                @endif
            @endforeach
        </select>

        <div class="flex absolute inset-y-0 right-0 items-center px-2 pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
            </svg>

        </div>
    </div>
</x-aura::fields.wrapper>
