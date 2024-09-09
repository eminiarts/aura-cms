@props([
  'id', 'slug', 'type' => '', 'children' => null
])
<button type="button" wire:click="addField('{{ addslashes($type) }}', '{{ $id }}', '{{ $slug }}')" class="flex relative justify-center items-center p-2 w-full text-center rounded-lg border-2 border-dashed border-gray-400/30 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
    <div class="mr-2">
      <svg class="w-5 h-5 text-gray-400" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 7H5.2C4.0799 7 3.51984 7 3.09202 7.21799C2.71569 7.40973 2.40973 7.71569 2.21799 8.09202C2 8.51984 2 9.0799 2 10.2V13.8C2 14.9201 2 15.4802 2.21799 15.908C2.40973 16.2843 2.71569 16.5903 3.09202 16.782C3.51984 17 4.07989 17 5.2 17H13M17 7H18.8C19.9201 7 20.4802 7 20.908 7.21799C21.2843 7.40973 21.5903 7.71569 21.782 8.09202C22 8.51984 22 9.0799 22 10.2V13.8C22 14.9201 22 15.4802 21.782 15.908C21.5903 16.2843 21.2843 16.5903 20.908 16.782C20.4802 17 19.9201 17 18.8 17H17M17 21L17 3M19.5 3.00001L14.5 3M19.5 21L14.5 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <span class="mt-0 text-xs font-medium text-gray-500">
      @if ($type == "Aura\Base\Fields\Tab")
        Add new tab
      @elseif ($type == 'Aura\Base\Fields\Panel')
        Add new panel
      @else
        Add new field
      @endif
    </span>
</button>
