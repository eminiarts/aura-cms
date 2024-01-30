{{-- Actions --}}
@if($this->bulkActions)
<div>
    <div class="relative ml-1">
        <x-aura::dropdown align="right" width="60">
        <x-slot name="trigger">
            <span class="inline-flex rounded-md">
                <button type="button"
                class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-700 transition bg-white border border-transparent rounded-md border-gray-500/30 hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 ">
                {{ __('Actions') }}
                
                
                <svg class="w-5 h-5 ml-2 -mr-1" xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd"
                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                clip-rule="evenodd" />
            </svg>
        </button>
    </span>
</x-slot>

<x-slot name="content">
    <div class="w-60">
        <div class="py-1" role="none">
            @if($this->bulkActions)
            @foreach($this->bulkActions as $action => $data)
            @if(is_array($data) && isset($data['modal']))
            <!-- if it's an array and has a modal, then open the modal -->
            <a wire:click="openBulkActionModal('{{ $action }}', {{json_encode($data)}})"
            class="flex items-center px-4 py-2 text-sm text-gray-700 group hover:bg-gray-100 cursor-pointer"
            role="menuitem" tabindex="-1" id="menu-item-6">
            {{ $data['label'] }}
        </a>
        @elseif(is_array($data) && optional($data)['method'] == 'collection')
        <!-- call collection action on model -->
        <a wire:click="bulkCollectionAction('{{ $action }}', {{json_encode($data)}})"
        class="flex items-center px-4 py-2 text-sm text-gray-700 group hover:bg-gray-100 cursor-pointer"
        role="menuitem" tabindex="-1" id="menu-item-6">
        {{ $data['label'] }}
    </a>
    @else
    <!-- if it's not an array, it's a string, so keep the old behavior -->
    <a wire:click="bulkAction('{{ $action }}')"
    class="flex items-center px-4 py-2 text-sm text-gray-700 group hover:bg-gray-100 cursor-pointer"
    role="menuitem" tabindex="-1" id="menu-item-6">
    {{ is_array($data) ? $data['label'] : $data }}
</a>
@endif
@endforeach
@endif


</div>
</div>
</x-slot>
</x-dropdown>
</div>
</div>
@endif