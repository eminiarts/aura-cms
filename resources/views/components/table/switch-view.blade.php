@if ($model->tableGridView() || $model->tableKanbanView())
    @php
        $switchBase = 'inline-flex relative items-center p-1.5 rounded-md transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500';
        $switchActive = 'bg-white text-gray-900 shadow-xs ring-1 ring-gray-950/[0.07] dark:bg-gray-700 dark:text-white dark:ring-white/10';
        $switchIdle = 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200';
    @endphp
    <div>
        <div class="inline-flex isolate gap-0.5 items-center p-0.5 rounded-lg bg-gray-950/[0.04] dark:bg-white/5">
            <button wire:click="switchView('list')" type="button"
                class="{{ $switchBase }} {{ ($currentView ?? 'list') == 'list' ? $switchActive : $switchIdle }}">
                <span class="sr-only">List View</span>
                <x-aura::icon icon="list" size="sm" />
            </button>

            @if ($model->tableGridView())
                <button wire:click="switchView('grid')" type="button"
                    class="{{ $switchBase }} {{ ($currentView ?? null) == 'grid' ? $switchActive : $switchIdle }}">
                    <span class="sr-only">Grid View</span>
                    <x-aura::icon icon="grid" size="sm" />
                </button>
            @endif

            @if ($model->tableKanbanView())
                <button wire:click="switchView('kanban')" type="button"
                    class="{{ $switchBase }} {{ ($currentView ?? null) == 'kanban' ? $switchActive : $switchIdle }}">
                    <span class="sr-only">Kanban View</span>
                    <x-aura::icon icon="kanban" size="sm" />
                </button>
            @endif
        </div>
    </div>
@endif
