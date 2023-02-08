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
    <x-fields.label :label="$label" />
    @endif

    @if($help)

    <div class="text-gray-300">
      <x-tooltip text="{{ $help }}" position="bottom" class="text-sm text-gray-400 bg-white">
        <x-icon icon="info" size='sm' />
      </x-tooltip>
    </div>
    @endif
  </div>

  <div class="">
    <x-input.text :attributes="$attributes"></x-input.text>

    @if($error)
      @error($error) <span class="error text-red-500 font-semibold text-sm">{{ $message }}</span> @enderror
    @endif

  </div>
</div>
