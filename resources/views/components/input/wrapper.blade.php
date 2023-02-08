@props([
'id',
'label' => null,
'placeholder' => null,
'class' => null,
'help' => null,
'helpIcon' => null,
'error' => null,
'required' => false,
'error' => null,
'wire',
'name',
])

<div >
  <div class="flex justify-between items-center">
    @if ($label)
    <x-aura::fields.label :label="$label" />
    @endif

    @if($help)

    <div class="text-gray-300">
      <x-aura::tooltip text="{{ $help }}" position="bottom" class="text-sm text-gray-400 bg-white">
        <x-aura::icon icon="info" size='sm' />
      </x-aura::tooltip>
    </div>
    @endif
  </div>

  <div class="">
    <x-aura::input.text :attributes="$attributes"></x-aura::input.text>

    @if($error)
      @error($error) <span class="error text-red-500 font-semibold text-sm">{{ $message }}</span> @enderror
    @endif

  </div>
</div>
