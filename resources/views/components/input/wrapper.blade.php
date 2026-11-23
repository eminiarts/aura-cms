@props([
'id',
'label' => null,
'placeholder' => null,
'class' => null,
'help' => null,
'helpIcon' => null,
'error' => null,
'required' => false,
'wire',
'name',
])

<div>

  <div class="flex justify-between items-center">
  @if ($label)
    <x-aura::fields.label :label="$label" />
    @else
    <div></div>
  @endif

    @if($help)

    <div class="text-gray-400 dark:text-gray-500">
      <x-aura::tooltip text="{{ $help }}" position="bottom" class="text-sm text-gray-400 bg-white">
        <x-aura::icon icon="info" size='sm' />
      </x-aura::tooltip>
    </div>
    @endif
  </div>


  @php
    $invalid = $error && isset($errors) && $errors->has($error);
  @endphp

  <div @class(['aura-field-invalid' => $invalid])>

    @if(optional($slot))
       {{ $slot }}
    @else
      <x-aura::input.text :attributes="$attributes"></x-aura::input.text>
    @endif

    @if($error)
      @error($error) <span class="aura-field-error error mt-1 block text-sm">{{ $message }}</span> @enderror
    @endif

  </div>
</div>
