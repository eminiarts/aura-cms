<div>
    <main class="" x-data="{
        showFilters: false,
        toggleFilters() {
            this.showFilters = !this.showFilters;
            this.$dispatch('inset-sidebar', {element: this.$refs.sidebar})
        },
        init() {

            Livewire.emit('tableMounted')

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

    {{-- if a view exists: aura.$model->pluralName().header, load it  --}}
    @if(View::exists($view = 'aura.' . str($model->pluralName())->slug . '.header'))
        @include($view)
    @elseif(View::exists('aura::' . $view))
        @include('aura::' . $view)
    @else
        <div class="flex items-center justify-between mt-6">
            <div>
                <h1 class="text-3xl font-semibold">{{ $model->pluralName() }}</h1>

                @if($this->parent)
                <span class="text-primary-500">from {{ $this->parent->name }}</span>
                @endif
                </h3>
            </div>

            <div>
                <div>
                    @if($this->createInModal)
                    <a href="#" wire:click.prevent="$emit('openModal', 'post.create-modal', {{ json_encode(['type' => $this->model->getType(), 'params' => [
                        'for' => $this->parent->getType(), 'id' => $this->parent->id
                    ]]) }})">
                           <x-aura::button>
                        <x-slot:icon>
                            <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>Create {{ $model->getName() }}</span>
                    </x-aura::button>
                        </a>
                    @else
                    <a href="{{ $this->createLink }}">
                        <x-aura::button>
                        <x-slot:icon>
                            <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>Create {{ $model->getName() }}</span>
                    </x-aura::button>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

        <div class="mt-6">

            {{-- @dump($this->headers) --}}
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
                            placeholder="Search for items" wire:model="search">
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-4">

                    {{-- Columns --}}
                    <div class="flex space-x-2">
                        @if($model->tableGridView())
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

                        @if($tableView == 'list')
                        @include('aura::components.table.settings')
                        @endif

                        <div>
                            <x-aura::button.border @click="toggleFilters()">
                                <x-slot:icon>
                                    <x-aura::icon icon="filter" />
                                    </x-slot>
                                    Filters
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
                                                class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-700 transition bg-white border border-transparent border-gray-500/30 rounded-md hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 ">
                                                Actions


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
                                                @foreach($this->bulkActions as $action => $label)
                                                <a wire:click="bulkAction('{{ $action }}')"
                                                    class="flex items-center px-4 py-2 text-sm text-gray-700 group hover:bg-gray-100"
                                                    role="menuitem" tabindex="-1" id="menu-item-6">
                                                    {{ $label }}
                                                </a>
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

            @include($model->tableGridView())

            @elseif($tableView == 'list')
            {{-- Table --}}
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
        </x-sidebar>
    </main>

</div>
