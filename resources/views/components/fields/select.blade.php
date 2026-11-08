<x-aura::fields.wrapper :field="$field">
  <div class="relative">
    <select
      @if($disabled = $field['field']->isDisabled($this->form, $field))
      disabled
      @endif
      @if(optional($field)['live'] === true)
      wire:model.live="form.fields.{{ optional($field)['slug'] }}"
      @else
      wire:model="form.fields.{{ optional($field)['slug'] }}"
      @endif
      id="aura_field_{{ optional($field)['slug'] }}"
    error="form.fields.{{ optional($field)['slug'] }}" name="post_fields_{{ optional($field)['slug'] }}" id="post_fields_{{ optional($field)['slug'] }}" class="block py-0 pr-9 pl-3 w-full h-9 text-base text-gray-900 bg-white bg-none rounded-lg border-0 ring-1 shadow-xs transition duration-150 appearance-none sm:text-sm ring-gray-950/10 hover:ring-gray-950/20 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 dark:bg-gray-800 dark:text-gray-100 dark:ring-white/10 dark:hover:ring-white/20 dark:disabled:bg-gray-800/50 [&:has(option[value='']:checked)]:text-gray-400 dark:[&:has(option[value='']:checked)]:text-gray-500 [&_option]:text-gray-900 dark:[&_option]:text-gray-100">

    @if(optional($field)['placeholder'])
    <option  value="">{{ optional($field)['placeholder'] }}</option>
    @else
    <option  value="">{{ __('Select :field…', ['field' => Str::lower(__(optional($field)['name']))]) }}</option>
    @endif

      @php
        $optionGroup = false;
        $options = optional($field)['options'];

        if (isset($this->model)) {
          $options = $field['field']->options($this->model, $field);
        }
      @endphp

     @foreach($options as $key => $value)
    @if(is_array($value) && is_string($key))
        {{-- This is a grouped option --}}
        <optgroup label="{{ $key }}">
            @foreach($value as $optionValue => $optionLabel)
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

    <div class="flex absolute inset-y-0 right-0 items-center pr-2.5 pointer-events-none" aria-hidden="true">
      <svg class="size-4 text-gray-400 dark:text-gray-500" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M4.25 6.25 8 10l3.75-3.75" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    </div>
  </div>
</x-aura::fields.wrapper>
