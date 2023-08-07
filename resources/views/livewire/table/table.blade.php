<div x-data="{
    selected: @entangle('selected'),
    rows: @entangle('rowIds'), 
    lastSelectedId: null,
    total: @js($this->rows->total()),
    selectPage: false,
    currentPage: @entangle('page').live,
    selectAll: @entangle('selectAll'),
    loading: false,
    
    init() {
        Livewire.on('selectedRows', (updatedSelected) => {
            this.selected = updatedSelected;
        });
        
        console.log('init table');
        
        if(this.selectAll) {
            this.selectPage = true;
        }
        
        // watch rows for changes
        this.$watch('rows', (rows) => {
            // Check if rows (array of ids) is included in this.selected. if so, set this.selectPage to true
            console.log('watch rows', rows, this.selected)
            
            //this.selectPage = rows.every(row => this.selected.includes(row.toString()));
        });
        
        this.$watch('currentPage', (rows) => {
            
            this.$nextTick(() => {
                
                
                console.log('currentPage', this.currentPage)
                console.log('rows', this.rows)
                console.log('selected', this.selected)
                
                /// check if this.rows are in this.selected. if so, set this.selectPage to true
                this.selectPage = this.rows.every(row => this.selected.includes(row));
                
                console.log('every', this.rows.every(row => this.selected.includes(row)))
                
            });
            
            
        });
    },
    
    selectCurrentPage() {
        this.$nextTick(() => {
            if (this.selectPage) {
                // add this.rows to existing this.selected, unique
                this.selected = Array.from(new Set([...this.selected.map(Number), ...this.rows.map(Number)]));
                
                // if all rows are selected, set this.selectAll to true
                this.selectAll = this.selected.length === this.total;
            } else {
                
                this.selectAll = false;
                
                // remove this.rows from existing this.selected with new Set
                this.selected = [...new Set([...this.selected.map(Number)].filter(item => !this.rows.map(Number).includes(item)))];
                console.log('selected on deselect', this.selected)
            }
        });
    },
    
    selectAllRows: async function () {
        
        this.loading = true
        
        let allSelected = await $wire.getAllTableRows()
        this.selectAll = true
        
        console.log(this.selected, 'selected')
        
        this.loading = false
        
        this.$nextTick(() => {
            
            // this.selected = allSelected with set
            this.selected = [...new Set([...this.selected.map(Number), ...allSelected.map(Number)])];
            this.selectPage = true;
        });
    },
    
    resetBulk() {
        this.selected = [];
        this.selectPage = false;
        this.selectAll = false;
    },
    
    deselectRows(ids) {
        for (id of ids) {
            let index = this.selected.indexOf(id)
            
            if (index === -1) {
                continue
            }
            
            this.selected.splice(index, 1)
            {{-- this.toggleRow(false, id) --}}
        }
    },
    
    toggleRow(event, id) {
        this.$nextTick(() => {
            // Check if shift key was pressed and last selected id exists
            if (event.shiftKey && this.lastSelectedId !== null) {
                // Get the indexes of the current and last selected rows
                const lastIndex = this.rows.indexOf(this.lastSelectedId);
                const currentIndex = this.rows.indexOf(id);
                
                // Determine the start and end indexes of the rows to be selected
                const start = Math.min(lastIndex, currentIndex);
                const end = Math.max(lastIndex, currentIndex);
                
                // If the current row is not already selected, remove all rows between start and end
                if (!this.selected.includes(id.toString())) {
                    this.selected = this.selected.filter(row => !this.rows.slice(start, end + 1).map(item => item.toString()).includes(row.toString()));
                }
                // Otherwise, add all rows between start and end
                else {
                    this.selected = [...this.selected, ...this.rows.slice(start, end + 1)].map(item => item.toString());
                    
                    // Remove duplicates from the selected rows
                    this.selected = this.selected.filter((item, index) => this.selected.indexOf(item) === index);
                }
            }
            
            this.lastSelectedId = id;
            
            // Select All
            if (this.selected.length === this.total) {
                this.selectAll = true;
            } else {
                this.selectAll = false;
            }
            
            // Select Page
            if (!this.selected.includes(id.toString())) {
                this.selectPage = false;
                
                console.log('deselect', id)
            }
            
        });
    }
}">
    {{-- Be aware that this file opens a div which closes at the end --}}
    @include('aura::components.table.context-menu')

    {{-- @dump($sorts) --}}

    <main class="" x-data="{
    showFilters: false,
    toggleFilters() {
        this.showFilters = !this.showFilters;
        this.$dispatch('inset-sidebar', {element: this.$refs.sidebar})
    },
    init() {
        
        Livewire.dispatch('tableMounted')
        
        const sortable = new window.Sortable(document.querySelectorAll('.sortable-wrapper'), {
            draggable: '.sortable',
            handle: '.drag-handle'
        });
        
        sortable.on('sortable:stop', () => {
            setTimeout(() => {
                @this.reorder(
                Array.from(document.querySelectorAll('.sortable'))
                .map(el => el.id)
                )
            }, 0)
        })
    }
}">

        @include('aura::components.table.header')

        <div class="mt-6">

            <div class="flex flex-col justify-between w-full mb-4 md:items-center md:flex-row">

                <div class="mb-4 md:mb-0">
                    <label for="table-search" class="sr-only">Search</label>
                    <div class="relative mt-1">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="currentColor"
                                viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd"
                                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <input type="text" id="table-search"
                            class="bg-white-50 border border-gray-500/30 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full md:w-80 pl-10 p-2.5  dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            placeholder="{{ __('Search for items') }}" wire:model.live="search">
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-4">

                    {{-- Columns --}}
                    <div class="flex space-x-2">
                        @if($this->model->tableGridView())
                        <div>
                            <span class="inline-flex rounded-md shadow-sm isolate">
                                <button wire:click="$set('tableView', 'grid')" type="button"
                                    class="relative inline-flex items-center px-2 py-2 text-sm font-medium bg-white border border-gray-500/30 rounded-l-md hover:bg-gray-50 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                    <span class="sr-only">Grid Layout</span>

                                    <x-aura::icon icon="grid" size="sm" />
                                </button>
                                <button wire:click="$set('tableView', 'list')" type="button"
                                    class="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium bg-white border border-gray-500/30 rounded-r-md hover:bg-gray-50 focus:z-10 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                                    <span class="sr-only">List Layout</span>
                                    <x-aura::icon icon="list" size="sm" />
                                </button>
                            </span>
                        </div>
                        @endif

                        @if($tableView == 'list' && $this->model->showTableSettings())
                        @include('aura::components.table.settings')
                        @endif

                        <div>
                            <x-aura::button.border @click="toggleFilters()">
                                <x-slot:icon>
                                    <x-aura::icon icon="filter" />
                                    </x-slot>
                                    {{ __('Filters') }}
                            </x-aura::button.border>
                        </div>
                    </div>

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
                                                class="flex items-center px-4 py-2 text-sm text-gray-700 group hover:bg-gray-100"
                                                role="menuitem" tabindex="-1" id="menu-item-6">
                                                {{ $data['label'] }}
                                            </a>
                                            @else
                                            <!-- if it's not an array, it's a string, so keep the old behavior -->
                                            <a wire:click="bulkAction('{{ $action }}')"
                                                class="flex items-center px-4 py-2 text-sm text-gray-700 group hover:bg-gray-100"
                                                role="menuitem" tabindex="-1" id="menu-item-6">
                                                {{ $data }}
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
                </div>
            </div>

            @if($tableView == 'grid')
            @include($this->model->tableGridView())
            @elseif($tableView == 'list')
            @include('aura::components.table.index')
            @endif

        </div>
        <x-aura::sidebar title="Filters" show="showFilters">
            <x-slot:heading class="font-semibold">
                <h3 class="text-xl font-semibold">
                    Filters
                </h3>
                </x-slot>
                @include('aura::components.table.filters')
                </x-aura::x-sidebar>
    </main>
</div> {{-- This closes the context menu --}}
</div>
