  <div>
      <span class="inline-flex isolate rounded-md shadow-sm">
          <button wire:click="switchView('list')" type="button"
              class="inline-flex relative items-center px-2 py-2 text-sm font-medium bg-white rounded-l-md border border-gray-500/30 hover:bg-gray-50 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700 dark:hover:bg-gray-800">
              <span class="sr-only">List View</span>
              <x-aura::icon icon="list" size="sm" />
          </button>

          @if ($model->tableGridView())
              <button wire:click="switchView('grid')" type="button"
                  class="inline-flex relative items-center px-2 py-2 -ml-px text-sm font-medium bg-white border border-gray-500/30 hover:bg-gray-50 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700 dark:hover:bg-gray-800 {{ $model->tableKanbanView() ? '' : 'rounded-r-md' }}">
                  <span class="sr-only">Grid View</span>
                  <x-aura::icon icon="grid" size="sm" />
              </button>
          @endif

          @if ($model->tableKanbanView())
          <button wire:click="switchView('kanban')" type="button"
              class="inline-flex relative items-center px-2 py-2 -ml-px text-sm font-medium bg-white rounded-r-md border border-gray-500/30 hover:bg-gray-50 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700 dark:hover:bg-gray-800">
              <span class="sr-only">Kanban View</span>
              <x-aura::icon icon="kanban" size="sm" />
          </button>
          @endif
      </span>
  </div>
