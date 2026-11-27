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
            {{-- Phones: a "more" (⋯) button, the board settings are its menu. --}}
            <x-aura::button.border class="max-sm:w-9 max-sm:px-0 max-sm:[&>span:first-child]:ml-0" title="{{ __('Board settings') }}" aria-haspopup="menu">
                <x-slot:icon>
                    <x-aura::icon.dots class="sm:hidden" />
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 max-sm:hidden">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                    </svg>
                </x-slot>
                <span class="sr-only sm:not-sr-only">{{ __('Board settings') }}</span>
            </x-aura::button.border>
        </x-slot>

        <x-slot name="content">
            <div class="w-60">
                <p class="px-4 pt-3 pb-1 text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Board settings') }}</p>
                <div class="p-2 pt-0 kanban-wrapper" drag-root="reorderKanbanStatuses" role="none">
                    @foreach($this->kanbanStatuses as $key => $status)
                        <label class="flex gap-2 items-center px-2 py-1.5 rounded-md transition-colors duration-150 cursor-pointer hover:bg-gray-100 dark:hover:bg-white/5 sortable"
                            for="status_{{$key}}" id="{{ $key }}">

                            <x-aura::input.checkbox wire:model.live="kanbanStatuses.{{ $key }}.visible" value="true" id="status_{{$key}}" />

                            <span class="flex flex-1 items-center text-sm text-gray-700 dark:text-gray-200 group" role="menuitem"
                                tabindex="-1" id="menu-item-{{$key}}">
                                {{ __($status['value']) }}
                            </span>

                            <div class="cursor-move drag-handle move-kanban-status">
                                <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" viewBox="0 0 24 24" fill="none"
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