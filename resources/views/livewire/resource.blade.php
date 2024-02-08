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

    }" class="flex items-center justify-between my-8">
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
                    <x-aura::input.text label="Name" placeholder="Name" value="{{ $resourceFields['type'] }}" disabled></x-aura::input>
                </div>

                <div class="w-full px-4 mb-0 md:w-1/3">
                    <x-aura::input.text label="Slug" placeholder="Slug" value="{{ $resourceFields['slug'] }}" disabled></x-aura::input>
                </div>


                <div class="flex items-end w-full px-4 mb-0 md:w-1/3">
                    <div class="flex-1">
                        <x-aura::input.text label="Icon" placeholder="Icon" wire:model="resourceFields.icon"></x-aura::input>
                    </div>

                    <div class="flex items-center justify-center w-10 h-10 mt-0 ml-2 border rounded-lg shadow-xs border-gray-500/30">
                        <span class="text-gray-500">
                            {!! $model->icon() !!}
                        </span>
                    </div>
                </div>

                <div class="flex items-end w-full px-4 mb-0 md:w-1/3">
                    <div class="flex-1">
                        <x-aura::input.text label="Group" placeholder="Group" wire:model="resourceFields.group"></x-aura::input>
                    </div>
                </div>
                <div class="flex items-end w-full px-4 mb-0 md:w-1/3">
                    <div class="flex-1">
                        <x-aura::input.text label="Dropdown" placeholder="Dropdown" wire:model="resourceFields.dropdown"></x-aura::input>
                    </div>
                </div>
                <div class="flex items-end w-full px-4 mb-0 md:w-1/3">
                    <div class="flex-1">
                        <x-aura::input.number label="Sort" placeholder="Sort" wire:model="resourceFields.sort" />
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- @dd($this->mappedFields) --}}
    {{-- @dump($this->mappedFields) --}}

    <div class="mt-8">

        @if ($hasGlobalTabs)

            <div class="flex flex-col w-full mt-0"
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

                <div class="mb-3 border-t rounded-b-lg border-gray-400/30 dark:border-gray-700"></div>

                <div class="flex flex-wrap py-2 " wire:key="resource-fields" x-data="resource">

                    @if($this->mappedFields)

                        @foreach($this->mappedFields as $tab)

                        <div class="flex flex-wrap min-w-full -mx-2 draggable-container reorder-item focus:outline-none" id="field_{{ $tab['_id'] }}" x-show="activeTab === {{ $loop->index }}" wire:key="resource-tab-{{ $tab['_id'] }}">

                            @if ( optional($tab)['fields'] )
                            @foreach($tab['fields'] as $field)

                                <div class="post-field-{{ optional($field)['slug'] }}-wrapper px-2 reorder-item draggable-item" id="field_{{ $field['_id'] }}" wire:key="pt-field-{{ $tab['_id'] }}">
                                    <style >
                                    .post-field-{{ optional($field)['slug'] }}-wrapper {
                                        width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
                                    }

                                    @media screen and (max-width: 768px) {
                                        .post-field-{{ optional($field)['slug'] }}-wrapper {
                                        width: 100%;
                                        }
                                    }
                                    </style>

                                    @include('aura::components.resource.show-field')
                                </div>

                                @if ($loop->last)
                                    <div class="w-full px-2">
                                        <x-aura::resource.add-field :id="$field['_id']" :slug="$field['slug']" :type="$field['type']" :children="$this->countChildren($field)"/>
                                    </div>
                                @endif
                            @endforeach

                            @else
                                <div x-cloak class="w-full px-2">

                                    <span class="text-sm font-semibold">Presets</span>
                                    <x-aura::button.transparent wire:click="insertTemplateFields({{ $tab['_id'] }}, '{{ $tab['slug'] }}', 'PanelWithSidebar')">Panel with Sidebar (70/30)</x-aura::button.transparent>
                                    <x-aura::button.transparent wire:click="insertTemplateFields({{ $tab['_id'] }}, '{{ $tab['slug'] }}', 'Plain')">Simple Panel with Text</x-aura::button.transparent>

                                    <x-aura::resource.add-field :id="$tab['_id']" :slug="$tab['slug']" type="field"/>
                                </div>
                            @endif

                        </div>
                        @endforeach
                    @else
                        <div class="w-full px-2">
                            <x-aura::resource.add-field :id="$field['_id']" :slug="$field['slug']" :type="$field['type']"/>
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

                <div class="flex flex-wrap py-2 draggable-container" x-data="resource" wire:key="resource2-fields">

                    @foreach($this->mappedFields as $field)

                        <div class="px-2 reorder-item draggable-item post-field-{{ optional($field)['slug'] }}-wrapper" id="field_{{ $field['_id'] }}" wire:key="pt-field-{{ $field['_id'] }}">
                            <style >
                                .post-field-{{ optional($field)['slug'] }}-wrapper {
                                    width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
                                }

                                @media screen and (max-width: 768px) {
                                    .post-field-{{ optional($field)['slug'] }}-wrapper {
                                    width: 100%;
                                    }
                                }
                            </style>

                            @include('aura::components.resource.show-field')
                        </div>

                        @if ($loop->last)
                            <div class="w-full px-2">
                                <x-aura::resource.add-field :id="$field['_id']" :slug="$field['slug']" :type="$field['type']" :children="$this->countChildren($field)"/>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="mt-6 text-center">
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
                    <p class="mt-1 text-sm text-gray-500">Get started by choosing a template for your resource.</p>

                    <div class="mt-0">
                        <div class="w-full p-6 ">

    <div class="grid justify-between w-full grid-flow-col space-x-8 auto-cols-max">
        <div class="flex flex-col items-center justify-between p-6 my-6 bg-gray-100 rounded-md">
            <div class="flex flex-col items-center my-6">
            <h3 class="text-lg font-semibold">Plain</h3>
            <span class="text-sm text-gray-500">Without Tabs and Panels</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-56 w-72" fill="none">
                <g filter="url(#a)"><path fill="#fff" d="M12 .5h269v196H12z"/><path fill="#F9FAFB" d="M35.636 23.844h220.403v157.691H35.636V23.845Z"/><path fill="#EFEFEF" d="M35.874 24.274h51.24v157.261h-51.24V24.274ZM92.824 37.206h22.274v4.283H92.824v-4.283ZM92.654 29.496h35.466v2.57H92.654v-2.57ZM92.654 49.776a2.621 2.621 0 0 1 2.62-2.62h152.488a2.621 2.621 0 0 1 2.621 2.62V174.23a2.621 2.621 0 0 1-2.621 2.621H95.275a2.62 2.62 0 0 1-2.621-2.621V49.776Z"/></g><defs><filter id="a" width="293" height="220" x="0" y=".5" color-interpolation-filters="sRGB" filterUnits="userSpaceOnUse"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" result="hardAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feMorphology in="SourceAlpha" radius="2" result="effect1_dropShadow_1596_94543"/><feOffset dy="4"/><feGaussianBlur stdDeviation="3"/><feColorMatrix values="0 0 0 0 0.0627451 0 0 0 0 0.0941176 0 0 0 0 0.156863 0 0 0 0.03 0"/><feBlend in2="BackgroundImageFix" result="effect1_dropShadow_1596_94543"/><feColorMatrix in="SourceAlpha" result="hardAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feMorphology in="SourceAlpha" radius="4" result="effect2_dropShadow_1596_94543"/><feOffset dy="12"/><feGaussianBlur stdDeviation="8"/><feColorMatrix values="0 0 0 0 0.0627451 0 0 0 0 0.0941176 0 0 0 0 0.156863 0 0 0 0.08 0"/><feBlend in2="effect1_dropShadow_1596_94543" result="effect2_dropShadow_1596_94543"/><feBlend in="SourceGraphic" in2="effect2_dropShadow_1596_94543" result="shape"/></filter></defs>
            </svg>

            <x-aura::button size="lg" wire:click="addTemplateFields('Plain')">Choose Template</x-aura::button>
        </div>

        <div class="flex flex-col items-center justify-between p-6 my-6 bg-gray-100 rounded-md">
            <div class="flex flex-col items-center my-6">
            <h3 class="text-lg font-semibold">Tabs</h3>
            <span class="text-sm text-gray-500">Use global Tabs to group Fields</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-56 w-72"  fill="none">
                <g filter="url(#a)"><path fill="#fff" d="M12.333.5h266v195h-266z"/><path fill="#F9FAFB" d="M35.769 22.28H254.69v157.554H35.769V22.279Z"/><path fill="#EFEFEF" d="M36.136 23.724H87v156.11H36.136V23.724ZM92.668 36.561h22.112v4.252H92.668v-4.252ZM92.5 28.907h35.206v2.552H92.499v-2.552ZM93.333 52.379h155.742v.85H93.333v-.85Z"/><path fill="#0019E4" d="M92.5 53.23h14.797v-.651H92.499v.65Z"/><path fill="#EFEFEF" d="M110.189 48.467h7.483v1.87h-7.483v-1.87Z"/><path fill="#0019E4" d="M96.157 48.467h7.483v1.87h-7.483v-1.87Z"/><path fill="#EFEFEF" d="M121.074 48.467h7.483v1.87h-7.483v-1.87ZM131.959 48.467h7.483v1.87h-7.483v-1.87ZM92.5 59.403A2.602 2.602 0 0 1 95.1 56.8h151.372a2.602 2.602 0 0 1 2.602 2.602v113.179a2.602 2.602 0 0 1-2.602 2.602H95.101a2.602 2.602 0 0 1-2.602-2.602V59.402Z"/></g><defs><filter id="a" width="290" height="219" x=".333" y=".5" color-interpolation-filters="sRGB" filterUnits="userSpaceOnUse"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" result="hardAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feMorphology in="SourceAlpha" radius="2" result="effect1_dropShadow_1596_94707"/><feOffset dy="4"/><feGaussianBlur stdDeviation="3"/><feColorMatrix values="0 0 0 0 0.0627451 0 0 0 0 0.0941176 0 0 0 0 0.156863 0 0 0 0.03 0"/><feBlend in2="BackgroundImageFix" result="effect1_dropShadow_1596_94707"/><feColorMatrix in="SourceAlpha" result="hardAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feMorphology in="SourceAlpha" radius="4" result="effect2_dropShadow_1596_94707"/><feOffset dy="12"/><feGaussianBlur stdDeviation="8"/><feColorMatrix values="0 0 0 0 0.0627451 0 0 0 0 0.0941176 0 0 0 0 0.156863 0 0 0 0.08 0"/><feBlend in2="effect1_dropShadow_1596_94707" result="effect2_dropShadow_1596_94707"/><feBlend in="SourceGraphic" in2="effect2_dropShadow_1596_94707" result="shape"/></filter></defs>
             </svg>

            <x-aura::button size="lg" wire:click="addTemplateFields('TabsWithPanels')">Choose Template</x-aura::button>
        </div>

        <div class="flex flex-col items-center justify-between p-6 my-6 bg-gray-100 rounded-md">
            <div class="flex flex-col items-center my-6">
            <h3 class="text-lg font-semibold">Tabs and Panels</h3>
            <span class="text-sm text-gray-500">Complex Models require both</span>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-56 w-72"  fill="none">
                <g filter="url(#a)"><path fill="#fff" d="M12.667.5h265v192.559h-265z"/><path fill="#F9FAFB" d="M36.102 22.28h212.611v153.012H36.103V22.279Z"/><path fill="#EFEFEF" d="M36.46 23.682h49.398v151.61H36.46V23.682ZM91.362 36.15h21.474v4.129H91.362v-4.13ZM91.198 28.716h34.192v2.478H91.198v-2.478ZM92.007 51.51H243.26v.827H92.007v-.826Z"/><path fill="#0019E4" d="M91.198 52.337h14.371v-.632h-14.37v.632Z"/><path fill="#EFEFEF" d="M108.378 47.712h7.267v1.817h-7.267v-1.817Z"/><path fill="#0019E4" d="M94.75 47.712h7.268v1.817H94.75v-1.817Z"/><path fill="#EFEFEF" d="M118.949 47.712h7.268v1.817h-7.268v-1.817ZM129.52 47.712h7.268v1.817h-7.268v-1.817ZM91.198 58.333a2.527 2.527 0 0 1 2.527-2.527h147.008a2.526 2.526 0 0 1 2.526 2.527v39.443a2.527 2.527 0 0 1-2.526 2.527H93.725a2.527 2.527 0 0 1-2.527-2.527V58.333ZM91.198 109.107a2.527 2.527 0 0 1 2.527-2.527h147.008a2.527 2.527 0 0 1 2.526 2.527v39.444a2.526 2.526 0 0 1-2.526 2.526H93.725a2.526 2.526 0 0 1-2.527-2.526v-39.444Z"/></g><defs><filter id="a" width="289" height="216.559" x=".667" y=".5" color-interpolation-filters="sRGB" filterUnits="userSpaceOnUse"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" result="hardAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feMorphology in="SourceAlpha" radius="2" result="effect1_dropShadow_1596_94828"/><feOffset dy="4"/><feGaussianBlur stdDeviation="3"/><feColorMatrix values="0 0 0 0 0.0627451 0 0 0 0 0.0941176 0 0 0 0 0.156863 0 0 0 0.03 0"/><feBlend in2="BackgroundImageFix" result="effect1_dropShadow_1596_94828"/><feColorMatrix in="SourceAlpha" result="hardAlpha" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"/><feMorphology in="SourceAlpha" radius="4" result="effect2_dropShadow_1596_94828"/><feOffset dy="12"/><feGaussianBlur stdDeviation="8"/><feColorMatrix values="0 0 0 0 0.0627451 0 0 0 0 0.0941176 0 0 0 0 0.156863 0 0 0 0.08 0"/><feBlend in2="effect1_dropShadow_1596_94828" result="effect2_dropShadow_1596_94828"/><feBlend in="SourceGraphic" in2="effect2_dropShadow_1596_94828" result="shape"/></filter></defs>
            </svg>

            <x-aura::button size="lg" wire:click="addTemplateFields('TabsWithPanels')">Choose Template</x-aura::button>
        </div>
    </div>

    <div class="flex justify-start w-full mb-2">
        <span class="text-sm font-semibold text-gray-500">More templates</span>
    </div>
    <div class="flex justify-start w-full space-x-2">


        <x-aura::button.border wire:click="addTemplateFields('Plain')">Plain</x-aura::button.border>
        <x-aura::button.border wire:click="addTemplateFields('TabsWithPanels')">TabsWithPanels</x-aura::button.border>
        <x-aura::button.border wire:click="addTemplateFields('PanelWithSidebar')">PanelWithSidebar</x-aura::button.border>
        <x-aura::button.border wire:click="addTemplateFields('PanelWithTabs')">PanelWithTabs</x-aura::button.border>
    </div>
</div>

                    </div>
                </div>
            @endif
        @endif


    </div>

    <livewire:aura::edit-resource-field />

    @once
    @push('scripts')

        <script >
            // when alpine is ready
            document.addEventListener('alpine:init', () => {
                // define an alpinejs component named 'userDropdown'
                Alpine.data('resource', () => ({
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
                                    @this.reorder(
                                        Array.from(document.querySelectorAll('.reorder-item')).map(el => el.id)
                                    )
                                })
                            });
                        })
                    }
                }));
            })
        </script>

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

    @endpush
    @endonce

</div>
