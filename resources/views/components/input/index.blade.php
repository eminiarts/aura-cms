@props([
  'suffix' => false,
  'prefix' => false,
  'size' => 'base',
  'class' => '',
  'label' => false,
  'error' => null,
])

@php
  // error: the validation key of this input. With an error the input gets aria-invalid, which
  // turns the .aura-field ring red (the message itself is printed by the wrapper).
  $invalid = $error && isset($errors) && $errors->has($error);
  // Same sizes as buttons: the height comes from .aura-control-{size} (--aura-control-height-*),
  // the radius, colours and states from .aura-field (resources/css/app.css). Sizes set padding-x and text only.
  $size = in_array($size, ['xs', 'sm', 'base', 'lg'], true) ? $size : 'base';
  $sizeClass = [
    'xs' => 'aura-control-xs px-2 text-xs',
    'sm' => 'aura-control-sm px-3 text-sm',
    'base' => 'aura-control-base px-3 text-sm',
    'lg' => 'aura-control-lg px-4 text-base',
  ][$size];
  $addonClass = [
    'xs' => 'px-2 text-xs',
    'sm' => 'px-3 text-sm',
    'base' => 'px-3 text-sm',
    'lg' => 'px-4 text-base',
  ][$size];
@endphp

<div class="w-full">
  @if ($label)
    <label class="inline-block mt-3 mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</label>
  @endif
  <div class="flex aura-field-group">
    @if ($prefix)
      <span class="aura-field aura-field-addon inline-flex items-center whitespace-nowrap {{ $addonClass }}">
          {{ $prefix }}
      </span>
    @endif
    <input {{ $attributes->merge(['class' => $class . ' aura-field w-full appearance-none z-[1] ' . $sizeClass, 'aria-invalid' => $invalid ? 'true' : null]) }}/>

    @if ($suffix)
      <span class="aura-field aura-field-addon inline-flex items-center whitespace-nowrap {{ $addonClass }}">
          {{ $suffix }}
      </span>
    @endif
  </div>
</div>
