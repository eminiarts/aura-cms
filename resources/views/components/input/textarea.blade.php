@props([
  'suffix' => false,
  'prefix' => false,
  'size' => 'base',
  'class' => '',
  'label' => false,
  'error' => null,
])

@php
  $invalid = $error && isset($errors) && $errors->has($error);
@endphp


<div class="w-full">
  @if ($label)
    <label class="inline-block mt-3 mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</label>
  @endif
  <div class="flex aura-field-group">
    @if ($prefix)
      <span class="aura-field aura-field-addon inline-flex items-center whitespace-nowrap {{ $size === 'xs' ? 'px-2 text-xs' : 'px-3 text-sm' }}">
          {{ $prefix }}
      </span>
    @endif
    <textarea {{ $attributes->merge(['class' => $class . ' aura-field block w-full appearance-none z-[1]' .
    ($size === 'xs' ? ' px-2 py-1 text-xs' : ' px-3 py-2 text-sm'),
    'aria-invalid' => $invalid ? 'true' : null,
    ]) }}></textarea>

    @if ($suffix)
      <span class="aura-field aura-field-addon inline-flex items-center whitespace-nowrap {{ $size === 'xs' ? 'px-2 text-xs' : 'px-3 text-sm' }}">
          {{ $suffix }}
      </span>
    @endif
  </div>
</div>
