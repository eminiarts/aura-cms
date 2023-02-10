<div>

    <x-slot name="header">
        <h3 class="text-xl font-semibold">Edit Post Type</h3>
    </x-slot>

    <div class="p-8 bg-white dark:bg-gray-800 rounded-xl shadow-card dark:shadow-none">
        <h2 class="text-xl font-semibold">Edit Post Type</h2>
        <div class="flex flex-wrap-mx-4">
            <div class="w-full px-4 mb-0 md:w-1/3">
                <x-input.text label="Name" placeholder="Name" value="{{ $model::getType() }}"></x-input>
                </div>
                <div class="w-full px-4 mb-0 md:w-1/3">
                    <x-input.text label="Slug" placeholder="Slug" value="{{ $model::getSlug() }}"></x-input>
                    </div>
                    {{-- <div class="w-full px-4 mb-0 md:w-1/3">
                        <x-input.text label="Label" placeholder="Placeholder"></x-input>
                        </div> --}}

                        <div class="w-full px-4 mt-4 mb-4 md:w-1/3">
                            <x-input.toggle label="Im Menü" model="true"></x-input>
                            </div>

                        </div>
                    </div>

                    <div class="p-8 mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-card dark:shadow-none">

                        <div class="flex flex-col w-full mt-4" x-data="{
                            activeTab: 0,
                            newTab: false,
                            newTabTitle: null,
                            tabs: @entangle('tabs'),
                            addTab() {
                                {{-- this.tabs.push(this.newTabTitle) --}}
                                @this.addTab(this.newTabTitle)

                                this.newTabTitle = null
                                this.newTab = false
                            }
                         }">
                            <div class="flex items-center py-2">
                                <template x-for="(tab, index) in tabs" :key="index">
                                    <button
                                    :class="{ 'border-b-2 border-primary-500': activeTab === index }"
                                    class="px-2 py-1 focus:outline-none"
                                    @click="activeTab = index"
                                    >
                                    <span x-text="tab"></span>
                                </button>
                            </template>

                            <template x-if="newTab">
                                <input
                                x-ref="newTab"
                                x-model="newTabTitle"
                                @keydown.enter="addTab"
                                @keydown.escape="newTab = null"
                                class="px-2 py-1 focus:outline-none"
                                type="text"
                                placeholder="New Tab"
                                >
                            </template>

                            <template x-if="!newTab">
                                <button
                                class="px-2 py-1 focus:outline-none"
                                @click="newTab = true"
                                >
                                <span class="ml-2">+ New Tab</span>
                            </button>
                        </template>


                    </div>

                    <div class="py-2">
                        <template x-for="(tab, index) in tabs" :key="index">
                            <div x-show="activeTab === index">
                                TAB 1
                            </div>
                        </template>
                    </div>
                </div>

                <div class="flex-1 py-2 bg-gray-100 rounded-b">
                    <div x-for="tab in tabs" :key="tab.id" x-show="activeTab === tab.id">
                        TAB 2
                    </div>
                </div>
            </div>

        </div>
        <div class="p-8 mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-card dark:shadow-none">
            <h2 class="mb-4 text-xl font-semibold">Custom Fields</h2>





            <div x-data="{
                active: 1,
                init() {
                    const sortable = new window.Sortable(document.querySelectorAll('.sortable'), {
                        draggable: '.sortable > div',
                        handle: '.handle',
                    });

                    sortable.on('sortable:stop', () => {
                        setTimeout(() => {
                            @this.reorder(
                            Array.from(document.querySelectorAll('.sortable > div'))
                            .map(el => el.id)
                            )
                        }, 0)
                    })
                }
            }" x-init="" drag-root="reorder" class="space-y-3 sortable">

            @php
            $layoutField = false;
            @endphp

            @foreach($fields as $key => $field)

            @php
            $name = Str::afterLast($field['type'], '\\');

            if (in_array($name, ['Tab', 'Panel', 'Layout'])) {
                $layoutField = true;
            }
            @endphp
            <div wire:key="field-{{ $key }}" id="{{ $field['slug'] }}" x-data="{
                id: '{{ $loop->index }}',
                hasConditionalLogic: {{ optional($field)['has_conditional_logic'] ? 'true' : 'false' }},
                get expanded() {
                    return this.active === this.id
                },
                set expanded(value) {
                    this.active = value ? this.id : null
                }
            }" role="region"

            @class([
            'border border-gray-400/30 rounded-md shadow-sm bg-white',
            'bg-yellow-50 !mt-8' => $name == 'Tab',
            'bg-yellow-50 !mt-8' => $name == 'Repeater',
            'bg-primary-50 !mt-8' => $name == 'Panel',
            '!ml-6' => $layoutField && !in_array($name, ['Tab', 'Panel', 'Layout']),
            ])
            >
            <div class="flex items-center">
                <div class="items-stretch pl-3 text-gray-400 cursor-move handle">
                    <x-aura::icon icon="move" size="sm" />
                </div>
                <h2 class="flex-1">
                    <button
                    x-on:click="expanded = !expanded"
                    :aria-expanded="expanded"
                    class="flex items-center justify-between w-full px-4 py-3"
                    >
                    <div class="w-full text-left">
                        <div class="flex items-center w-full space-x-1">
                            <span class="text-base font-semibold text-black">{{ $field['name'] }}</span>
                            <span class="flex-1 text-gray-400">({{ $field['slug'] ?? '' }})</span>
                            <span class="inline-block px-2 py-1 text-xs font-medium text-green-600 rounded-full bg-green-50">
                                <span>{{ $name }}</span> Field
                            </span>
                        </div>
                    </div>
                    <span x-show="expanded" aria-hidden="true" class="ml-4 text-lg">&minus;</span>
                    <span x-show="!expanded" aria-hidden="true" class="ml-4 text-lg">&plus;</span>
                </button>
            </h2>
        </div>


        <div x-show="expanded" x-collapse>
            <div class="px-4 pb-4 border-t border-gray-400/30">

                <div class="">

                    {{-- @dump($model->field($field)) --}}
                    {{-- @dump($field) --}}

                    <div class="flex flex-wrap items-end -mx-4">
                        <div class="w-full px-4 mb-0 md:w-1/3">
                            <x-input.wrapper label="Field Name" help="This is the name which will appear on the pages" placeholder="Field Name" wire:model.defer="fields.{{ $key}}.name" error="fields.{{ $key }}.name"></x-input.wrapper>
                        </div>
                        <div class="w-full px-4 mb-0 md:w-1/3">
                            <x-input.wrapper label="Field Slug" help="Single word, no spaces. Underscores and dashes allowed" placeholder="Field Slug" wire:model.lazy="fields.{{ $key}}.slug" error="fields.{{ $key }}.slug"></x-input.wrapper>
                        </div>

                        <div class="w-full px-4 mb-0 md:w-1/3">

                            <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                            <select id="type" name="type" class="block w-full py-2 pl-3 pr-10 mt-1 text-base border-gray-500/30 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                <optgroup label="Grundlage">
                                    <option value="Text">Text</option>
                                    <option value="Textarea">Text (mehrzeilig)</option>
                                    <option value="Number">Number</option>
                                    <option value="E-Mail">E-Mail</option>
                                    <option value="Password">Password</option>
                                </optgroup>
                                <optgroup label="Inhalt">
                                    <option value="Image">Image</option>
                                    <option value="File">File</option>
                                    <option value="Galerie">Galerie</option>
                                    <option value="WYSIWYG">WYSIWYG</option>
                                </optgroup>
                                <optgroup label="Auswahl">
                                    <option value="Select">Select</option>
                                    <option value="Multiselect">Multiselect</option>
                                    <option value="Radio">Radio</option>
                                    <option value="Checkbox">Checkbox</option>
                                </optgroup>
                                <optgroup label="Sonstiges">
                                    <option value="Date">Date</option>
                                    <option value="Time">Time</option>
                                    <option value="DateTime">DateTime</option>
                                    <option value="Color">Color</option>
                                </optgroup>
                                <optgroup label="Relational">
                                    <option value="Post-Objekt">Post-Objekt</option>
                                    <option value="Taxonomie">Taxonomie</option>
                                    <option value="Benutzer">Benutzer</option>
                                </optgroup>
                                <optgroup label="Layout">
                                    <option value="Header">Header</option>
                                    <option value="Akkordeon">Akkordeon</option>
                                    <option value="Tab">Tab</option>
                                    <option value="Gruppe">Gruppe</option>
                                    <option value="Repeater">Repeater</option>
                                    <option value="Flexible Inhalte">Flexible Inhalte</option>
                                </optgroup>
                            </select>



                            {{-- <x-input.select label="Type"></x-input.select> --}}

                        </div>

                        <div class="w-full px-4 mb-0 md:w-1/2">
                            <x-input.text label="Instructions" placeholder="Instructions" wire:model.defer="fields.{{ $key}}.instructions" error="fields.{{ $key }}.instructions"></x-input.text>
                        </div>

                        <div class="w-full px-4 mb-0 md:w-1/2">

                            <x-input.text label="Validation" placeholder="required|min:3|max:255|number..." wire:model.defer="fields.{{ $key }}.validation"></x-input.text>
                        </div>

                        <div class="w-full">


                            <div class="w-full px-4 mt-4 mb-4">
                                <x-input.toggle label="Conditional Logic" wire:model.defer="fields.{{ $key}}.has_conditional_logic"></x-input.toggle>
                            </div>

                            <div class="py-4 border-t border-b border-gray-400/30" x-show="hasConditionalLogic">
                                {{-- For each rule group --}}

                                @if(optional($field)['conditional_logic'])
                                @foreach ($field['conditional_logic'] as $groupKey => $group)
                                <div>

                                    {{-- For each rule --}}
                                    @foreach ($group as $ruleKey => $rule)
                                    <div class="flex items-end w-full px-3">
                                        <div class="w-full px-1 md:w-2/5">
                                            <label for="type" class="block text-sm font-medium text-gray-700">Show this field if</label>

                                            <select wire:model="fields.{{ $key }}.conditional_logic.{{ $groupKey }}.{{ $ruleKey }}.param" id="type" name="type" class="block w-full py-2 pl-3 pr-10 mt-1 text-base border-gray-500/30 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                                @foreach($fields as $key2 => $field2)
                                                <option @disabled($field['name'] == $field2['name']) value="{{ $field2['slug'] }}">{{ $field2['name'] }} </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="w-full px-1 md:w-1/5">
                                            <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                                            <select wire:model="fields.{{ $key }}.conditional_logic.{{ $groupKey }}.{{ $ruleKey }}.operator" id="type" name="type" class="block w-full py-2 pl-3 pr-10 mt-1 text-base border-gray-500/30 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                                <option value="is_equal">is equal to</option>
                                                <option value="is_not_equal">is not equal to</option>
                                                <option value="is_empty">has no value</option>
                                                <option value="is_not_empty">has any value</option>
                                                <option value="matches_pattern">Value matches pattern</option>
                                                <option value="contains">Value contains</option>
                                            </select>
                                        </div>
                                        <div class="w-full px-1 md:w-2/5">
                                            @if ($fields[$key]['conditional_logic'][$groupKey][$ruleKey]['operator'] == 'is_empty')
                                            empty
                                            @else
                                            <x-input.wrapper label="Value" placeholder="Value" wire:model.defer="fields.{{ $key }}.conditional_logic.{{ $groupKey }}.{{ $ruleKey }}.value" error="fields.{{ $key }}.conditional_logic.{{ $groupKey }}.{{ $ruleKey }}.value"></x-input.wrapper>
                                            @endif

                                        </div>
                                        <div class="px-1">
                                            <x-aura::button.light wire:click="addConditionalLogicRule('{{ $key }}', '{{ $groupKey }}')">And</x-aura::button.light>
                                        </div>
                                        <div class="px-1">
                                            <x-aura::button.danger wire:click="removeConditionalLogicRule('{{ $key }}', '{{ $groupKey }}', '{{ $ruleKey }}')">-</x-aura::button.danger>
                                        </div>
                                    </div>
                                    @endforeach

                                </div>
                                <div class="px-4 py-2"><span>Or</span></div>
                                @endforeach
                                @endif

                                <div class="px-4">

                                    <x-aura::button size="xs" wire:click="addConditionalLogicRuleGroup('{{ $key }}')">Add rule group</x-aura::button>
                                </div>

                            </div>
                        </div>

                        <div class="w-full pl-2" x-data="{showAdvanced: false}">
                            <div class="flex items-center">
                                <x-aura::button.transparent size="xs" @click="showAdvanced = !showAdvanced">Advanced Settings +</x-aura::button.transparent>
                                <div class="h-[1px] bg-gray-200 flex-1 ml-2"></div>
                            </div>

                            <div x-show="showAdvanced" class="flex flex-wrap w-full">
                                <div class="w-full px-4 mb-0 md:w-1/3">
                                    <x-input.wrapper label="Width" help="This is the name which will appear on the pages" placeholder="Width" wire:model.defer="fields.{{ $key}}.style.width" error="fields.{{ $key }}.style.width"></x-input.wrapper>
                                </div>
                                <div class="w-full px-4 mb-0 md:w-1/3">
                                    <x-input.wrapper label="CSS Classes" help="CSS Classes" placeholder="CSS Classes" wire:model.lazy="fields.{{ $key}}.style.class" error="fields.{{ $key }}.style.class"></x-input.wrapper>
                                </div>

                                <div class="w-full px-4 mb-0 md:w-1/3">
                                    <x-input.wrapper label="ID" help="ID" placeholder="CSS ID" wire:model.lazy="fields.{{ $key }}.style.id" error="fields.{{ $key }}.style.id"></x-input.wrapper>
                                </div>

                                <div class="w-full px-4">
                                    <div class="w-full px-4 mt-4 mb-4">
                                        <x-input.toggle2 label="Show in Table?" wire:model.defer="fields.{{ $key}}.style.showInTable"></x-input.toggle2>
                                    </div>
                                </div>
                            </div>
                        </div>



                        {{-- <div class="w-full px-4 mt-4 mb-4">
                            <x-input.toggle label="Enabled" model="true"></x-input>
                            </div> --}}
                        </div>

                        <div class="flex justify-end mt-4">
                            <x-aura::button.danger wire:click="removeField('{{ $key }}')">
                                <div class="flex items-center space-x-4">
                                    <x-aura::icon icon="trash" size="sm" />
                                    Delete Field
                                </div>
                            </x-aura::button.danger>
                        </div>

                    </div>


                </div>
            </div>
        </div>

        @endforeach



    </div>

    <div class="block mt-6">
        <x-aura::button.light wire:click="addField">
            <div class="flex items-center space-x-4">
                <x-aura::icon icon="plus" size="sm" />
                Add Field
            </div>
        </x-aura::button.light>
    </div>

    @if (count($errors->all()))
    <div class="block">
        <div class="mt-8 form_errors">
            <strong class="block text-red-600">Unfortunately, there were still the following validation errors:</strong>
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



    <x-aura::button size="xl" wire:click="save">Save</x-aura::button>


</div>


{{-- <x-slot name="sidebar">
    Sidebar Content
</x-slot> --}}

</div>
