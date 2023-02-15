<x-aura::fields.wrapper :field="$field">
  <div class="relative">

    <select
      @if(optional($field)['defer'] === false)
      wire:model.debounce.200ms="post.fields.{{ optional($field)['slug'] }}"
      @else
      wire:model.defer="post.fields.{{ optional($field)['slug'] }}"
      @endif

    error="post.fields.{{ optional($field)['slug'] }}" name="post_fields_{{ optional($field)['slug'] }}" id="post_fields_{{ optional($field)['slug'] }}" class="block w-full px-3 py-2 pl-3 pr-10 mt-1 text-base bg-white border-gray-500/30 rounded-lg shadow-xs appearance-none focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700 sm:text-sm">

    <option>Select {{ optional($field)['name'] }}...</option>

      @php
        $optionGroup = false;
      @endphp

      {{-- @dd($field['options']) --}}
      @foreach($field['options'] as $key => $option)
        <!-- if key starts with "option_group" -->
        @if (Str::startsWith($key, 'option_group'))
          @if ($optionGroup)
            </optgroup>
          @endif
          @php
            $optionGroup = true;
          @endphp
          <optgroup label="{{ $option }}">

        @else

            {{-- if key and values are set on the option, show it --}}
            @if (is_array($option))
              <option value="{{ $option['key'] }}">{{ $option['value'] }}</option>
            @else

            <option value="{{ $key }}">{{ $option }}</option>
            @endif
        @endif

        <!-- If last option in loop -->
        @if ($loop->last && $optionGroup)
          </optgroup>
        @endif
      @endforeach
    </select>

    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
      <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
      <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
    </svg>

    </div>
  </div>
</x-aura::fields.wrapper>