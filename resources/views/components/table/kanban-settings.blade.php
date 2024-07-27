<div id="kanban-settings-button" class="relative close-on-select-false" x-data="{
    init() {
        const sortable = new window.Sortable(document.querySelector('[drag-root=\'reorderKanbanStatuses\']'), {
                draggable: '.sortable',
                handle: '.drag-handle',
                animation: 150,
            
            });
    
            sortable.on('sortable:stop', () => {
                setTimeout(() => {
                    @this.reorderKanbanStatuses(
                        Array.from(document.querySelectorAll('.sortable'))
                        .map(el => el.id)
                    )
                }, 0)
            })
    }
}">
    <x-aura::dropdown align="right" width="60" :closeOnSelect="false">
        <x-slot name="trigger">
            <x-aura::button.border>
                <x-slot:icon>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                    </svg>
                </x-slot>
                {{ __('Kanban Settings') }}
            </x-aura::button.border>
        </x-slot>

        <x-slot name="content">
            <div class="w-60">
                <div class="p-4 kanban-wrapper" drag-root="reorderKanbanStatuses" role="none">
                    @foreach($this->kanbanStatuses as $key => $status)
                        <label class="flex items-center py-2 cursor-pointer space-x-2 hover:bg-gray-100 dark:hover:bg-gray-900 sortable"
                            for="status_{{$key}}" id="{{ $key }}">

                            <x-aura::input.checkbox wire:model.live="kanbanStatuses.{{ $key }}.visible" value="true" id="status_{{$key}}" />

                            <span class="flex flex-1 items-center px-4 text-sm text-gray-700 dark:text-gray-200 group" role="menuitem"
                                tabindex="-1" id="menu-item-{{$key}}">
                                {{ __($status['value']) }}
                            </span>

                            <div class="cursor-move drag-handle move-kanban-status">
                                <svg class="mr-2 w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 8.5H21M3 15.5H21" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        </x-slot>
    </x-aura::dropdown>
</div>