@php
  $options = optional($field)['options'];

  if (isset($this->model)) {
    $options = $field['field']->options($this->model, $field);
  }

  $options = $options ?? [];
  $slug = optional($field)['slug'];
  $placeholder = optional($field)['placeholder'] ?: __('Select :field…', ['field' => Str::lower(__(optional($field)['name']))]);
  $disabled = $field['field']->isDisabled($this->form, $field);

  // Options that name a color (['key', 'value', 'color']) show as tags in the trigger and the list, the
  // same tag the board, list and record view show. Plain and grouped options keep the native select.
  $tagOptions = collect($options)->isNotEmpty()
    && collect($options)->every(fn ($option) => is_array($option) && isset($option['key'], $option['value']))
    && collect($options)->contains(fn ($option) => ! empty($option['color']));
  $required = is_string(optional($field)['validation']) && str($field['validation'])->contains('required');
@endphp
<x-aura::fields.wrapper :field="$field">
  @if ($tagOptions)
  @php
    // The empty choice clears an optional value; a required field has nothing to clear to.
    $choices = collect($options)->map(fn ($option) => ['key' => (string) $option['key'], 'label' => __($option['value']), 'color' => $option['color'] ?? null])->values();
    $keys = ($required ? collect() : collect([''])) ->merge($choices->pluck('key'))->values();
  @endphp
  <div class="relative" wire:ignore data-select-tags
    x-data="{
      open: false,
      active: 0,
      keys: @js($keys),
      value: $wire.entangle('form.fields.{{ $slug }}', {{ optional($field)['live'] === true ? 'true' : 'false' }}),
      is(key) { return String(this.value ?? '') === key },
      show() {
        if (this.open) return this.hide();
        this.open = true;
        this.active = Math.max(0, this.keys.findIndex(key => this.is(key)));
        this.$nextTick(() => this.$refs.listbox.focus());
      },
      hide(focus = true) {
        this.open = false;
        if (focus) this.$refs.button.focus();
      },
      choose(key) { this.value = key; this.hide() },
      move(step) { this.active = (this.active + step + this.keys.length) % this.keys.length },
    }"
    x-on:keydown.escape.prevent.stop="hide()"
    x-on:click.outside="open && hide(false)">
    <button type="button" x-ref="button" id="aura_field_{{ $slug }}"
      @if($disabled) disabled @endif
      x-on:click="show()"
      x-on:keydown.arrow-down.prevent="show()"
      x-on:keydown.arrow-up.prevent="show()"
      aria-haspopup="listbox" :aria-expanded="open"
      class="flex relative items-center pr-9 pl-2 w-full h-9 text-left bg-white rounded-lg ring-1 shadow-xs transition duration-150 cursor-default sm:text-sm ring-gray-950/10 hover:ring-gray-950/20 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-50 dark:bg-gray-800 dark:ring-white/10 dark:hover:ring-white/20 dark:disabled:bg-gray-800/50">
      <span x-show="is('')" class="pl-1 text-base text-gray-400 truncate sm:text-sm dark:text-gray-500">{{ $placeholder }}</span>
      @foreach ($choices as $choice)
        <span x-show="is(@js($choice['key']))" x-cloak class="aura-tag {{ $choice['color'] ? 'aura-tag-status '.$choice['color'] : '' }}">{{ $choice['label'] }}</span>
      @endforeach
      <span class="flex absolute inset-y-0 right-0 items-center pr-2.5 pointer-events-none" aria-hidden="true">
        <svg class="text-gray-400 size-4 dark:text-gray-500" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M4.25 6.25 8 10l3.75-3.75" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </span>
    </button>

    <ul x-show="open" x-cloak x-ref="listbox" tabindex="-1" role="listbox" aria-labelledby="aura_field_{{ $slug }}"
      x-on:keydown.arrow-down.prevent="move(1)"
      x-on:keydown.arrow-up.prevent="move(-1)"
      x-on:keydown.enter.prevent="choose(keys[active])"
      x-on:keydown.space.prevent="choose(keys[active])"
      x-on:keydown.tab="hide(false)"
      x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
      class="overflow-auto absolute z-20 p-1 mt-1 w-full max-h-60 bg-white rounded-lg ring-1 shadow-lg focus:outline-none ring-gray-950/10 dark:bg-gray-800 dark:ring-white/10">
      @unless ($required)
        <li role="option" :aria-selected="is('')" x-on:click="choose('')" x-on:mousemove="active = 0"
          :class="active === 0 && 'bg-gray-950/[0.04] dark:bg-white/5'"
          class="flex justify-between items-center px-2 h-8 text-sm text-gray-400 rounded-md cursor-default select-none dark:text-gray-500">
          {{ $placeholder }}
        </li>
      @endunless
      @foreach ($choices as $index => $choice)
        @php($position = $index + ($required ? 0 : 1))
        <li role="option" :aria-selected="is(@js($choice['key']))" x-on:click="choose(@js($choice['key']))" x-on:mousemove="active = {{ $position }}"
          :class="active === {{ $position }} && 'bg-gray-950/[0.04] dark:bg-white/5'"
          class="flex justify-between items-center px-2 h-8 rounded-md cursor-default select-none">
          <span class="aura-tag {{ $choice['color'] ? 'aura-tag-status '.$choice['color'] : '' }}">{{ $choice['label'] }}</span>
          <svg x-show="is(@js($choice['key']))" class="text-primary-600 size-4 shrink-0 dark:text-primary-400" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path d="m3.5 8.5 3 3 6-7" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </li>
      @endforeach
    </ul>
  </div>
  @else
  <div class="relative">
    <select
      @if($disabled)
      disabled
      @endif
      @if(optional($field)['live'] === true)
      wire:model.live="form.fields.{{ optional($field)['slug'] }}"
      @else
      wire:model="form.fields.{{ optional($field)['slug'] }}"
      @endif
      id="aura_field_{{ optional($field)['slug'] }}"
    error="form.fields.{{ optional($field)['slug'] }}" name="post_fields_{{ optional($field)['slug'] }}" id="post_fields_{{ optional($field)['slug'] }}" class="block py-0 pr-9 pl-3 w-full h-9 text-base text-gray-900 bg-white bg-none rounded-lg border-0 ring-1 shadow-xs transition duration-150 appearance-none sm:text-sm ring-gray-950/10 hover:ring-gray-950/20 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 dark:bg-gray-800 dark:text-gray-100 dark:ring-white/10 dark:hover:ring-white/20 dark:disabled:bg-gray-800/50 [&:has(option[value='']:checked)]:text-gray-400 dark:[&:has(option[value='']:checked)]:text-gray-500 [&_option]:text-gray-900 dark:[&_option]:text-gray-100">

    <option  value="">{{ $placeholder }}</option>

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
  @endif
</x-aura::fields.wrapper>
