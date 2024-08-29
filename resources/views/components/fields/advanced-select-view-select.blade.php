<div class="flex items-center space-x-3">
    @if(isset($field['thumbnail']) && $field['thumbnail'] && $field['thumbnail'] != '')
        @if($item->{$field['thumbnail']})
            <x-aura::image :id="$item->{$field['thumbnail']}" alt="{{ $item->title() }}" class="object-cover w-12 h-12 rounded shrink-0" />
        @else
            <div class="flex justify-center items-center w-12 h-12 bg-gray-100 rounded shrink-0">
            </div>
        @endif
    @endif
    <div>
      <span class="font-semibold text-gray-800">{{ $item->title() }}</span>
      @if(isset($field['description']) && $field['description'] && $field['description'] != '')
        @if($item->{$field['description']})
          <div class="line-clamp-1">
            <p class="mt-1 text-sm text-gray-500">{{ $item->{$field['description']} }}</p>
          </div>
        @endif
      @endif
    </div>
</div>
