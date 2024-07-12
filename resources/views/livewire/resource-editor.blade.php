<div>
    @section('title', 'Edit ' . $model::getType() . ' Fields • ')

    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-7 h-7 mr-0" />
        <x-aura::breadcrumbs.li :href="route('aura.resource.editor', $model::getSlug())" title="Resources" />
        <x-aura::breadcrumbs.li :title="$model::getType()" />
    </x-aura::breadcrumbs>

    {{-- <x-slot name="header">
        <h3 class="text-xl font-semibold">Edit Post Type</h3>
    </x-slot> --}}

    <div x-data="{
        fileSaved: false,
        init() {
            // Listen for the beforeunload event on the window
            var vm = this;
            window.addEventListener('beforeunload', function (event) {
                if (vm.fileSaved) {
                    event.preventDefault();
                    event.returnValue = 'You have unsaved changes. Are you sure you want to leave this page?';
                }
            });

            // Listen for the livewire event savedField
            $wire.on('finishedSavingFields', () => {
                vm.fileSaved = true;
                setTimeout(() => {
                    vm.fileSaved = false;
                }, 4000);
            });
        }

    }" class="flex justify-between items-center my-8">
        <div>
            <h1 class="text-3xl font-semibold">Edit {{ $model::getType() }} Fields</h1>
        </div>

        <div class="flex items-center space-x-2">
            @include('aura::livewire.resource.actions')
            <x-aura::button size="lg" wire:click="save">
                <div wire:loading>
                    <x-aura::icon.loading />
                </div>
                Save
            </x-aura::button>
        </div>
    </div>

    <div class="mt-8">
        <div class="mt-4">
            @if (count($errors->all()))
                <div class="block">
                    <div class="mt-8 form_errors">

                        <strong class="block text-red-600">Unfortunately, there were still the following validation
                            errors:</strong>
                        <div class="text-red-600 prose">
                            <ul>
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex flex-wrap -mx-4">
                <div class="px-4 mb-0 w-full md:w-1/3">
                    <x-aura::input.text label="Name" placeholder="Name" value="{{ $resource['type'] }}" disabled></x-aura::input>
                </div>

                <div class="px-4 mb-0 w-full md:w-1/3">
                    <x-aura::input.text label="Slug" placeholder="Slug" value="{{ $resource['slug'] }}" disabled></x-aura::input>
                </div>


                <div class="flex items-end px-4 mb-0 w-full md:w-1/3">
                    <div class="flex-1">
                        <x-aura::input.text label="Icon" placeholder="Icon" wire:model="resource.icon"></x-aura::input>
                    </div>

                    <div class="flex justify-center items-center mt-0 ml-2 w-10 h-10 rounded-lg border shadow-xs border-gray-500/30">
                        <span class="text-gray-500">
                            {!! $model->icon() !!}
                        </span>
                    </div>
                </div>

                <div class="flex items-end px-4 mb-0 w-full md:w-1/3">
                    <div class="flex-1">
                        <x-aura::input.text label="Group" placeholder="Group" wire:model="resource.group"></x-aura::input>
                    </div>
                </div>
                <div class="flex items-end px-4 mb-0 w-full md:w-1/3">
                    <div class="flex-1">
                        <x-aura::input.text label="Dropdown" placeholder="Dropdown" wire:model="resource.dropdown"></x-aura::input>
                    </div>
                </div>
                <div class="flex items-end px-4 mb-0 w-full md:w-1/3">
                    <div class="flex-1">
                        <x-aura::input.number label="Sort" placeholder="Sort" wire:model="resource.sort" />
                    </div>
                </div>

            </div>
        </div>
    </div>


    <div class="mt-8">

        @if ($hasGlobalTabs)

            <div class="flex flex-col mt-0 w-full"
                x-data="{
                    activeTab: 0,

                    tabs: @entangle('globalTabs').live,

                    init() {
                    }
                }
            ">
                <div class="flex items-center pt-3 -mb-px">
                    <div class="flex">
                        <template x-for="(tab, index) in tabs" :key="index">
                            <div
                                :class="{
                                    'border-primary-600 text-primary-700 dark:border-primary-500 dark:text-primary-400 whitespace-nowrap px-4 border-b-2 font-semibold text-sm': activeTab === index,
                                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-500/30 dark:hover:text-gray-500 dark:hover:border-gray-600 whitespace-nowrap px-4 border-b-2 font-semibold text-sm' : activeTab !== index
                                }"
                                class="flex px-2 py-1 focus:outline-none"
                            >
                                <button
                                    @click="activeTab = index"
                                >
                                    <span x-text="tab.name"></span>
                                </button>
                                <button class="ml-2 text-gray-400 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-500" @click="$wire.openSidebar(tab.slug, '{{ $model::getSlug() }}')">
                                    <x-aura::icon.edit class="w-4 h-4" />
                                </button>
                            </div>
                        </template>
                    </div>

                    <button
                        class="px-2 py-1 focus:outline-none"
                        wire:click="addNewTab()"
                        >
                        <span class="inline-block px-2 ml-2 text-sm text-gray-500 whitespace-nowrap hover:text-gray-700 dark:text-gray-600 dark:hover:text-gray-500">+ Add Tab</span>
                    </button>
                </div>

                <div class="mb-3 rounded-b-lg border-t border-gray-400/30 dark:border-gray-700"></div>

                <div class="flex flex-wrap py-2" wire:key="resource-fields" x-data="{
                    init() {
                        Alpine.nextTick(() => {
                            const sortable = new window.Sortable(document.querySelectorAll('.draggable-container'), {
                                draggable: '.draggable-item',
                                handle: '.draggable-handle',
                                mirror: {
                                    constrainDimensions: true,
                                },
                            });

                            sortable.on('sortable:stop', () => {
                                Alpine.nextTick(() => {
                                    console.log('2', Array.from(document.querySelectorAll('.reorder-item')).map(el => el.id));
                                    @this.reorder(
                                        Array.from(document.querySelectorAll('.reorder-item')).map(el => el.id)
                                    )
                                })
                            });
                        })
                    }
                }">

                    @if($this->mappedFields)
                        @foreach($this->mappedFields as $tab)

                        <div class="flex flex-wrap -mx-2 min-w-full draggable-container reorder-item focus:outline-none" id="field_{{ $tab['_id'] }}" x-show="activeTab === {{ $loop->index }}" wire:key="resource-tab-{{ $tab['_id'] }}">

                            @if ( optional($tab)['fields'] )

                            @foreach($tab['fields'] as $field)


                                <div class="resource-field-{{ optional($field)['slug'] }}-wrapper px-2 reorder-item draggable-item" id="field_{{ $field['_id'] }}" wire:key="pt-field-{{ $field['_id'] }}">
                                    <style >
                                    .resource-field-{{ optional($field)['slug'] }}-wrapper {
                                        width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
                                    }

                                    @media screen and (max-width: 768px) {
                                        .resource-field-{{ optional($field)['slug'] }}-wrapper {
                                        width: 100%;
                                        }
                                    }
                                    </style>
                                    @include('aura::components.resource.show-field')
                                </div>

                                @if ($loop->last)
                                    <div class="px-2 w-full">
                                        <x-aura::resource.add-field :id="$field['_id']" :slug="$field['slug']" :type="$field['type']" :children="$this->countChildren($field)"/>
                                    </div>
                                @endif
                            @endforeach

                            @else
                                <div x-cloak class="px-2 w-full">

                                    <span class="text-sm font-semibold">Presets</span>
                                    <x-aura::button.transparent wire:click="insertTemplateFields({{ $tab['_id'] }}, '{{ $tab['slug'] }}', 'PanelWithSidebar')">Panel with Sidebar (70/30)</x-aura::button.transparent>
                                    <x-aura::button.transparent wire:click="insertTemplateFields({{ $tab['_id'] }}, '{{ $tab['slug'] }}', 'Plain')">Simple Panel with Text</x-aura::button.transparent>

                                    <x-aura::resource.add-field :id="$tab['_id']" :slug="$tab['slug']" type="field"/>
                                </div>
                            @endif

                        </div>
                        @endforeach

                    @endif
                </div>


            </div>

        @else
        
            {{-- <div>
                <button
                    class="px-2 py-1 focus:outline-none"
                    wire:click="addNewTab()"
                >
                    <span class="ml-2">+ New Tab</span>
                </button>
            </div> --}}


            @if (count($this->mappedFields) > 0)

                <div class="flex flex-wrap py-2 draggable-container" x-data="{
                    init() {
                        Alpine.nextTick(() => {
                            const sortable = new window.Sortable(document.querySelectorAll('.draggable-container'), {
                                draggable: '.draggable-item',
                                handle: '.draggable-handle',
                                mirror: {
                                    constrainDimensions: true,
                                },
                            });

                            sortable.on('sortable:stop', () => {
                                Alpine.nextTick(() => {
                                    console.log('1', Array.from(document.querySelectorAll('.reorder-item')).map(el => el.id));
                                    @this.reorder(
                                        Array.from(document.querySelectorAll('.reorder-item')).map(el => el.id)
                                    )
                                })
                            });
                        })
                    }
                }" wire:key="resource2-fields">

                    @foreach($this->mappedFields as $field)

                        <div class="px-2 reorder-item draggable-item resource-field-{{ optional($field)['slug'] }}-wrapper" id="field_{{ $field['_id'] }}" wire:key="pt-field-{{ $field['_id'] }}">
                            <style >
                                .resource-field-{{ optional($field)['slug'] }}-wrapper {
                                    width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
                                }

                                @media screen and (max-width: 768px) {
                                    .resource-field-{{ optional($field)['slug'] }}-wrapper {
                                    width: 100%;
                                    }
                                }
                            </style>

                            @include('aura::components.resource.show-field')
                        </div>

                        @if ($loop->last)
                            <div class="px-2 w-full">
                                <x-aura::resource.add-field :id="$field['_id']" :slug="$field['slug']" :type="$field['type']" :children="$this->countChildren($field)"/>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="text-center">
                    <div class="py-6">
                        <h3 class="mt-4 text-base font-semibold text-gray-900">No fields</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by choosing a template for your resource.</p>
                    </div>

                    <div class="mt-0">
                        <div class="py-4 w-full">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 w-full">
                                <div class="flex flex-col justify-between items-center p-6 bg-gray-100 rounded-md transition-all duration-300 border-2 border-transparent hover:border-2 hover:border-blue-500 cursor-pointer" wire:click="addTemplateFields('g')">
                                    <div class="flex flex-col items-center mb-6 text-center">
                                        <h3 class="text-lg font-semibold">Plain</h3>
                                        <span class="text-sm text-gray-500">Without Tabs and Panels</span>
                                    </div>
                                    
                                    <x-aura::icon.template-plain class="mb-6" />
                                </div>

                                <div class="flex flex-col justify-between items-center p-6 bg-gray-100 rounded-md transition-all duration-300 border-2 border-transparent hover:border-2 hover:border-blue-500 cursor-pointer" wire:click="addTemplateFields('TabsWithPanels')">
                                    <div class="flex flex-col items-center mb-6 text-center">
                                        <h3 class="text-lg font-semibold">Tabs</h3>
                                        <span class="text-sm text-gray-500">Use global Tabs to group Fields</span>
                                    </div>

                                    <x-aura::icon.template-tabs class="mb-6" />
                                </div>

                                <div class="flex flex-col justify-between items-center p-6 bg-gray-100 rounded-md transition-all duration-300 border-2 border-transparent hover:border-2 hover:border-blue-500 cursor-pointer" wire:click="addTemplateFields('TabsWithPanels')">
                                    <div class="flex flex-col items-center mb-6 text-center">
                                        <h3 class="text-lg font-semibold">Tabs and Panels</h3>
                                        <span class="text-sm text-gray-500">Complex Models require both</span>
                                    </div>

                                    <x-aura::icon.template-tabs-panels class="mb-6" />
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            @endif
        @endif


    </div>

    <livewire:aura::edit-resource-field />

    @once
    @assets

        <style >
            .draggable--original *{
                opacity: 0.5;
            }
            .draggable--over {
                opacity: 0.5;
            }

            .aura-card-small {
                width: 100%;
                transform-origin: 0% 50%;
                transition: .2s all ease;
            }
            .draggable-mirror .aura-card-small {
                opacity: 1;
                box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);

                transform: rotate(-1deg);

                min-width: 400px;
            }
        </style>

    @endassets
    @endonce

</div>
