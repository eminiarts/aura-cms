@php
    $fieldItemIds = $row->fields[$field['slug']] ?? [];
    $fieldItems = [];

    if (!is_array($fieldItemIds)) {
        $fieldItemIds = [$fieldItemIds];
    }

    if ($field['resource']) {
        $resourceClass = $field['resource'];
        $fieldItems = $resourceClass::whereIn('id', $fieldItemIds)->get();
    }
@endphp

<div class="flex space-x-2">
    @foreach($fieldItems as $item)
        @if(isset($field['view_index']))
            @include($field['view_index'], ['item' => $item])
        @else
            <div class="flex items-center px-1 py-1 bg-gray-100 rounded-full">
                @if(isset($field['thumbnail']) && $field['thumbnail'] && $field['thumbnail'] != '')
                    @if($item->{$field['thumbnail']})
                        <x-aura::image :id="$item->{$field['thumbnail']}" alt="{{ $item->title() }}" class="object-cover w-6 h-6 rounded-full shrink-0" />
                    @else
                        <div class="flex justify-center items-center w-6 h-6 bg-gray-300 rounded-full">
                            {{-- <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"></path>
                            </svg> --}}
                        </div>
                    @endif
                @endif
                <span class="px-2 text-sm font-medium text-gray-800 truncate">{{ $item->title() }}</span>
            </div>
        @endif
    @endforeach
</div>
