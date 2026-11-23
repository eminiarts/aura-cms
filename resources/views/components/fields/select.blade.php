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
      @error('form.fields.' . optional($field)['slug']) aria-invalid="true" @enderror
      name="post_fields_{{ optional($field)['slug'] }}" class="aura-field aura-control-base block pr-10 pl-3 w-full text-sm appearance-none">

    @if(optional($field)['placeholder'])
    <option  value="">{{ optional($field)['placeholder'] }}</option>
    @else
    <option  value="">{{ __('Select :field', ['field' => __(optional($field)['name'])]) }}...</option>
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

    <x-aura::input.indicator />
  </div>
</x-aura::fields.wrapper>
