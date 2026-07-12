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


  <div class="">

    @if(optional($slot))
       {{ $slot }}
    @else
      <x-aura::input.text :attributes="$attributes"></x-aura::input.text>
    @endif

    @if($error)
      @error($error) <span class="error mt-1 block text-sm text-red-600 dark:text-red-400">{{ $message }}</span> @enderror
    @endif

  </div>
</div>
