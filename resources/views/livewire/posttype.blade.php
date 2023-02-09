<div>
    @section('title', 'Edit ' . $model::getType() . ' Fields • ')

    <x-breadcrumbs>
        <x-breadcrumbs.li :href="route('dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-7 h-7 mr-0" />
        <x-breadcrumbs.li :href="route('aura.posttype.edit', $model::getSlug())" title="Posttypes" />
        <x-breadcrumbs.li :title="$model::getType()" />
    </x-breadcrumbs>

    {{-- <x-slot name="header">
        <h3 class="text-xl font-semibold">Edit Post Type</h3>
    </x-slot> --}}

    <div class="flex items-center justify-between my-8">
        <div>
            <h1 class="text-3xl font-semibold">Edit {{ $model::getType() }} Fields</h1>
        </div>

        <div>
            <x-button size="lg" wire:click="save">
                <div wire:loading>
                    <x-aura::icon.loading />
                </div>
                Save
            </x-button>
        </div>
    </div>

    <div class="mt-8">
        {{-- <h2 class="text-3xl font-semibold">Edit Post Type</h2> --}}

        <div class="mt-4">

            @if (count($errors->all()))
                <div class="block">
                    <div class="mt-8 form_errors">

                        <strong class="block text-red-600">Unfortunately, there were still the following validation
                            errors:</strong>
                        <div class="prose text-red-600">
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
                <div class="w-full px-4 mb-0 md:w-1/3">
                    <x-input.text label="Name" placeholder="Name" value="{{ $postTypeFields['type'] }}" disabled></x-input>
                </div>

                <div class="w-full px-4 mb-0 md:w-1/3">
                    <x-input.text label="Slug" placeholder="Slug" value="{{ $postTypeFields['slug'] }}" disabled></x-input>
                </div>

                <div class="w-full px-4 mt-4 mb-4 md:w-1/3">
                    <x-input.toggle label="Show in sidebar" model="true"></x-input>
                </div>


                <div class="flex items-end w-full px-4 mb-0 md:w-1/3">
                    <div class="flex-1">
                        <x-input.text label="Icon" placeholder="Icon" wire:model="postTypeFields.icon"></x-input>
                    </div>

                    <div class="flex items-center justify-center w-10 h-10 mt-0 ml-2 border border-gray-500/30 rounded-lg shadow-xs">
                        <span class="text-gray-500">
                            {!! $model->icon() !!}
                        </span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="mt-8">

        @if ($hasGlobalTabs)

            <div class="flex flex-col w-full mt-0"
                x-data="{
                    activeTab: 0,

                    tabs: @entangle('globalTabs') ,

                    init() {
                        console.log('init tabs', this.tabs);
                    }
                }
            ">
                <div class="flex items-center pt-3 -mb-px">
                    <div class="flex">
                        <template x-for="(tab, index) in tabs" :key="index">
                            <div
                                :class="{
                                    'border-primary-600 text-primary-700 dark:border-primary-500 dark:text-primary-500 whitespace-nowrap px-4 border-b-2 font-semibold text-sm': activeTab === index,
                                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-500/30 dark:hover:text-gray-500 dark:hover:border-gray-600 whitespace-nowrap px-4 border-b-2 font-semibold text-sm' : activeTab !== index
                                }"
                                class="flex px-2 py-1 focus:outline-none"
                            >
                                <button
                                    @click="activeTab = index"
                                >
                                    <span x-text="tab.name"></span>
                                </button>
                                <button class="ml-2 text-gray-400 hover:text-gray-600" @click="$wire.openSidebar(tab.slug, '{{ $model::getSlug() }}')">
                                    <x-aura::icon.edit class="w-4 h-4" />
                                </button>
                            </div>
                        </template>
                    </div>

                    <button
                        class="px-2 py-1 focus:outline-none"
                        wire:click="addNewTab()"
                        >
                        <span class="inline-block px-2 ml-2 text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap">+ Add Tab</span>
                    </button>
                </div>

                <div class="mb-3 border-t border-gray-400/30 rounded-b-lg dark:border-gray-700"></div>


                <div class="flex flex-wrap py-2 draggable-container" x-data="posttype" wire:key="posttype-fields">

                    @if($this->mappedFields)
                        @foreach($this->mappedFields as $tab)

                        <div class="flex flex-wrap min-w-full -mx-2 reorder-item draggable-item focus:outline-none" id="field_{{ $tab['_id'] }}" x-show="activeTab === {{ $loop->index }}" wire:key="posttype-tab-{{ $tab['_id'] }}">
                            {{-- <span>{{ $tab['name'] }}</span> --}}

                            <!-- if $tab['fields'] -->
                            @if ( optional($tab)['fields'] )
                            @foreach($tab['fields'] as $field)

                                <style>
                                .post-field-{{ optional($field)['slug'] }}-wrapper {
                                    width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
                                }

                                @media screen and (max-width: 768px) {
                                    .post-field-{{ optional($field)['slug'] }}-wrapper {
                                    width: 100%;
                                    }
                                }
                                </style>

                                <div class="post-field-{{ optional($field)['slug'] }}-wrapper px-2 reorder-item draggable-item" id="field_{{ $field['_id'] }}" wire:key="pt-field-{{ $tab['_id'] }}">
                                    <x-posttype.show-field :field="$field" :slug="$slug" />
                                </div>

                                @if ($loop->last)
                                    <div class="w-full px-2">
                                        <x-posttype.add-field :id="$field['_id']" :slug="$field['slug']" :type="$field['type']" :children="isset($field['fields']) ? count($field['fields']) : 0"/>
                                    </div>
                                @endif
                            @endforeach

                            @else
                                <div class="w-full px-2">
                                    <x-posttype.add-field :id="$tab['_id']" :slug="$tab['slug']" :type="$tab['type']"/>
                                </div>
                            @endif

                        </div>
                        @endforeach
{{--                        @dump($this->all())--}}
                    @else
{{--                        @dump($this->mappedFields)--}}
{{--                        @dump($this->all())--}}
                                <div class="w-full px-2">
                                    <x-posttype.add-field :id="$field['_id']" :slug="$field['slug']" :type="$field['type']"/>
                                </div>
                    @endif
                </div>


            </div>

        @else
            <div>
                <button
                    class="px-2 py-1 focus:outline-none"
                    wire:click="addNewTab()"
                >
                    <span class="ml-2">+ New Tab</span>
                </button>
            </div>
            @if (count($this->mappedFields) > 0)
                <div x-data="posttype" class="flex flex-wrap min-w-full mt-2 -mx-2 reorder-item draggable-item">
                    @foreach($this->mappedFields as $field)
                        <style>
                            .post-field-{{ optional($field)['slug'] }}-wrapper {
                                width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
                            }

                            @media screen and (max-width: 768px) {
                                .post-field-{{ optional($field)['slug'] }}-wrapper {
                                width: 100%;
                                }
                            }
                        </style>
                        <div class="px-2 reorder-item draggable-item post-field-{{ optional($field)['slug'] }}-wrapper" id="field_{{ $field['_id'] }}" wire:key="pt-field-{{ $field['_id'] }}">
                            <x-posttype.show-field :field="$field" :slug="$slug" />
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center">
                    <div class="flex justify-center text-gray-300">
                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 18H42M18 18L18 42M15.6 6H32.4C35.7603 6 37.4405 6 38.7239 6.65396C39.8529 7.2292 40.7708 8.14708 41.346 9.27606C42 10.5595 42 12.2397 42 15.6V32.4C42 35.7603 42 37.4405 41.346 38.7239C40.7708 39.8529 39.8529 40.7708 38.7239 41.346C37.4405 42 35.7603 42 32.4 42H15.6C12.2397 42 10.5595 42 9.27606 41.346C8.14708 40.7708 7.2292 39.8529 6.65396 38.7239C6 37.4405 6 35.7603 6 32.4V15.6C6 12.2397 6 10.5595 6.65396 9.27606C7.2292 8.14708 8.14708 7.2292 9.27606 6.65396C10.5595 6 12.2397 6 15.6 6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>

                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M24 24L42 24M24 6L24 42M15.6 6H32.4C35.7603 6 37.4405 6 38.7239 6.65396C39.8529 7.2292 40.7708 8.14708 41.346 9.27606C42 10.5595 42 12.2397 42 15.6V32.4C42 35.7603 42 37.4405 41.346 38.7239C40.7708 39.8529 39.8529 40.7708 38.7239 41.346C37.4405 42 35.7603 42 32.4 42H15.6C12.2397 42 10.5595 42 9.27606 41.346C8.14708 40.7708 7.2292 39.8529 6.65396 38.7239C6 37.4405 6 35.7603 6 32.4V15.6C6 12.2397 6 10.5595 6.65396 9.27606C7.2292 8.14708 8.14708 7.2292 9.27606 6.65396C10.5595 6 12.2397 6 15.6 6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>

                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 30H42M15.6 6H32.4C35.7603 6 37.4405 6 38.7239 6.65396C39.8529 7.2292 40.7708 8.14708 41.346 9.27606C42 10.5595 42 12.2397 42 15.6V32.4C42 35.7603 42 37.4405 41.346 38.7239C40.7708 39.8529 39.8529 40.7708 38.7239 41.346C37.4405 42 35.7603 42 32.4 42H15.6C12.2397 42 10.5595 42 9.27606 41.346C8.14708 40.7708 7.2292 39.8529 6.65396 38.7239C6 37.4405 6 35.7603 6 32.4V15.6C6 12.2397 6 10.5595 6.65396 9.27606C7.2292 8.14708 8.14708 7.2292 9.27606 6.65396C10.5595 6 12.2397 6 15.6 6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>



                    </div>
                    <h3 class="mt-4 text-base font-semibold text-gray-900">No fields</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by choosing a template for your posttype.</p>
                    <div class="mt-6">
                        <button onclick="Livewire.emit('openModal', 'choose-template')" type="button" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white border border-transparent rounded-md shadow-sm bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                        <!-- Heroicon name: mini/plus -->
                        <svg class="w-5 h-5 mr-2 -ml-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        Choose Template
                        </button>

                        <x-button wire:click="addTemplateFields('Plain')">Plain</x-button>
                        <x-button wire:click="addTemplateFields('TabsWithPanels')">TabsWithPanels</x-button>
                        <x-button wire:click="addTemplateFields('PanelWithTabs')">PanelWithTabs</x-button>
                    </div>
                </div>
            @endif
        @endif


    </div>

    <livewire:edit-posttype-field />

    @once
    @push('scripts')

        <script>
            // when alpine is ready
            document.addEventListener('alpine:init', () => {
                // define an alpinejs component named 'userDropdown'
                Alpine.data('posttype', () => ({
                    init() {
                        console.log('init posttype');
                        const sortable = new window.Sortable(document.querySelectorAll('.draggable-container'), {
                            draggable: '.draggable-item',
                            handle: '.draggable-handle',
                            mirror: {
                                constrainDimensions: true,
                            },
                        });

                        sortable.on('sortable:stop', () => {
                            setTimeout(() => {
                                @this.reorder(
                                    Array.from(document.querySelectorAll('.reorder-item')).map(el => el.id)
                                )
                            }, 0)
                        });


                        // const containerTwoCapacity = 3;
                        // const containerTwoParent = sortable.containers[1].parentNode;
                        // let currentMediumChildren;
                        // let capacityReached;
                        // let lastOverContainer;

                        // // --- Draggable events --- //
                        // sortable.on('drag:start', (evt) => {
                        //     currentMediumChildren = sortable.getDraggableElementsForContainer(sortable.containers[1])
                        //     .length;
                        //     console.log('drag:start', currentMediumChildren, evt);
                        //     capacityReached = currentMediumChildren >= containerTwoCapacity;
                        //     lastOverContainer = evt.sourceContainer;
                        //     containerTwoParent.classList.toggle('!bg-red-500', capacityReached);
                        // });

                        // sortable.on('drag:stop', (evt) => {
                        //     currentMediumChildren = sortable.getDraggableElementsForContainer(sortable.containers[1])
                        //     .length;
                        //     console.log('drag:stop', currentMediumChildren, evt);
                        //     // remove !bg-red-500 from classlist
                        //     containerTwoParent.classList.remove('!bg-red-500');
                        // });

                        // sortable.on('sortable:sort', (evt) => {
                        //     if (!capacityReached) {
                        //         return;
                        //     }

                        //     const sourceIsCapacityContainer = evt.dragEvent.sourceContainer === sortable.containers[1];

                        //     if (!sourceIsCapacityContainer && evt.dragEvent.overContainer === sortable.containers[1]) {
                        //     evt.cancel();
                        //     }
                        // });

                        // sortable.on('sortable:sorted', (evt) => {
                        //     if (lastOverContainer === evt.dragEvent.overContainer) {
                        //     return;
                        //     }

                        //     lastOverContainer = evt.dragEvent.overContainer;
                        // });
                    }
                }));
            })
        </script>

        <style>
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

    @endpush
    @endonce

</div>
