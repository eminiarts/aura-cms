@php
    // Api from Field is more important than from Field Class
    $api = optional($field)['api'] ?? false;

    if (!$api) {
        $api = optional($field['field'])->api ?? false;
    }

    // Create Modal
    $create = optional($field)['create'] ?? false;

    // Multiple from Field is more important than from Field Class
    $multiple = optional($field)['multiple'] ?? true;

    if (!isset($field['multiple'])) {
        $multiple = optional($field['field'])->multiple ?? true;
    }

    if ($api) {
        $values = [];
        $selectedValues = $field['field']->selectedValues($field['resource'], optional($this->form['fields'])[$field['slug']], $field);
    } else {
        $values = $field['field']->values($field['resource'], $field);
        $selectedValues = [];
    }

@endphp


<x-aura::fields.wrapper :field="$field">
    <style>
        .dragging-item {
            position: absolute;
            pointer-events: none;
            opacity: 0.8;
            z-index: 100;
        }

        .shadow-item {
            opacity: 0.4;
        }
    </style>

    <div wire:ignore class="w-full" x-data="{
        value: $wire.entangle('form.fields.{{ $field['slug'] }}'),
        items: @js($values),
        selectedItems: @js($selectedValues),
        deselectedItems: [],
        api: @js($api),
        model: @js($field['resource']),
        field: @js($field['type']),
        fullField: @js($field),
        multiple: @js($multiple),
        slug: @js($field['slug']),
        searchable: {{ Js::from($field['searchable'] ?? true) }},
        csrf: document.querySelector('meta[name=\'csrf-token\']').getAttribute('content'),
        search: null,
        showListbox: false,
        loading: false,

        dragging: false,
        dragIndex: null,
        dragX: 0,

        boundStopDragging: null,
        boundMoveItem: null,

        updateSelectedItemsOrder() {
            const newOrder = Array.from(this.$refs.selectedItemsContainer.querySelectorAll('.draggable-selectmany-item')).map(item => parseInt(item.getAttribute('data-id')));

            const validIds = newOrder.filter(item => !isNaN(item));

            this.$nextTick(() => {
                this.value = newOrder;

                console.log('updateSelectedItemsOrder', this.items, this.selectedItems, newOrder);

                this.selectedItems = newOrder.map(id => this.selectedItems.find(item => item.id === id));
                this.$wire.set('form.fields.{{ $field['slug'] }}', validIds);
                this.showListbox = false;
            });
        },

        init() {
            console.log('init', 'selectedItems', this.selectedItems, 'value', this.value, 'items', this.items, 'multiple', this.multiple, 'api', this.api);
            this.boundStopDragging = this.stopDragging.bind(this);
            this.boundMoveItem = this.moveItem.bind(this);
            if (this.api) {
                this.fetchApi();
            } else {
                if (!this.value) {
                    this.value = [];
                }
                if (!this.multiple) {
                    console.log('not multiple');
                    this.selectedItems = this.items.filter(item => this.value.includes(item.id));
                    console.log('selectedItems', this.selectedItems);
                } else {
                    this.selectedItems = this.items.filter(item => this.value.includes(item.id));
                }
            }

            {{-- console.log(this.items, this.value); --}}

            Livewire.on('resourceCreated', data => {
                this.items.push({ id: data.form.id, title: data.title });
                this.toggleItem({ id: data.form.id, title: data.title });
            })

            // Watch this.search and fetch new values, debounce for 500ms
            this.$watch('search', () => {
                if (this.api) {
                    this.fetchApi();
                }
            }, { debounce: 500 });
        },

        fetchApi() {
            this.loading = true;

            fetch('/admin/api/fields/values', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'x-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({
                        model: this.model,
                        slug: this.slug,
                        field: this.field,
                        fullField: this.fullField,
                        search: this.search,
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    this.items = data.sort((a, b) => a.id - b.id);
                    this.loading = false;

                    console.log('new items: ', this.items);
                });
        },

        get filteredItems() {
            var items = this.items;
            if (!this.api && this.search) {
                return items.filter(item => item.title.toLowerCase().includes(this.search.toLowerCase()));
            }
            if (items.length === 0) {

                if (this.selectedItems && this.selectedItems.length > 0) {
                    return this.selectedItems;
                }

                return [];
            }

            if (!this.selectedItems || this.selectedItems.length === 0) {
                return items;
            }

            return [...this.selectedItems, ...items].filter((item, index, self) => self.findIndex(i => i.id === item.id) === index).sort((a, b) => a.id - b.id);
        },

        isDisabled(item) {
            return false;
        },

        isSelected(item) {
            return this.value.includes(item.id);
        },

        toggleListbox() {
            this.showListbox = !this.showListbox;

            if (!this.showListbox) {
                this.$refs.listboxButton.focus();
            }

            if (this.showListbox && this.searchable) {
                this.$nextTick(() => {
                    this.$refs.searchField.focus();
                });
            }
        },

        closeListbox() {
            if (this.showListbox) {
                this.showListbox = false;
            }
        },

        selectItem(item) {
            if (!this.multiple) {
                if (this.value && this.value[0] === item.id) {
                    // Deselect if clicking the same item
                    this.value = [];
                    this.selectedItems = [];
                } else {
                    this.value = [item.id];
                    this.selectedItems = [item];
                }
            } else {
                if (!Array.isArray(this.value)) {
                    this.value = [];
                }

                if (!Array.isArray(this.selectedItems)) {
                    this.selectedItems = [];
                }

                if (this.isSelected(item)) {
                    this.value = this.value.filter(i => i !== item.id);
                    if (!this.deselectedItems.find(i => i.id === item.id)) {
                        this.deselectedItems.push(item);
                    }
                } else {
                    if (!this.selectedItems.find(i => i.id === item.id)) {
                        this.selectedItems.push(item);
                    }
                    if (!this.value.includes(item.id)) {
                        this.value.push(item.id);
                    }
                    this.deselectedItems = this.deselectedItems.filter(i => i.id !== item.id);
                }
            }
        },

        selectedItem(id) {
            if (!id) {
                return false;
            }
            if (this.selectedItems.length === 0) {
                return;
            }
            if (!this.selectedItems) {
                return;
            }
            return this.selectedItems.find(item => item.id === id).title;
        },

        selectedItemMarkup(id) {
            console.log('selectedItemMarkup', id, this.selectedItems);
            if (!id) {
                return false;
            }
            if (this.selectedItems.length === 0) {
                return;
            }
            if (!this.selectedItems) {
                return;
            }
            return this.selectedItems.find(item => item.id === id);
        },

        focusNext(e) {
            const items = this.$refs.listbox.querySelectorAll(`[role='option']`);
            const active = e.target;
            if (!active) {
                items[0].focus();
                return;
            }
            const index = Array.from(items).indexOf(active);
            if (index === items.length - 1) {
                items[0].focus();
                return;
            }
            items[index + 1].focus();
        },
        focusPrevious(e) {
            const items = this.$refs.listbox.querySelectorAll(`[role='option']`);
            const active = e.target;
            if (!active) {
                items[items.length - 1].focus();
                return;
            }
            const index = Array.from(items).indexOf(active);
            if (index === 0) {
                items[items.length - 1].focus();
                return;
            }
            items[index - 1].focus();
        },

        startDragging(index, event) {
            this.dragging = true;
            this.dragIndex = index;
            const containerRect = this.$refs.selectedItemsContainer.getBoundingClientRect();
            this.$refs.draggingItem.style.left = event.clientX - containerRect.left + 'px';
            this.$refs.draggingItem.style.top = event.target.offsetTop + 'px';
            window.addEventListener('mouseup', this.boundStopDragging);
            window.addEventListener('mousemove', this.boundMoveItem);
        },

        moveItem(event) {
            if (!this.dragging) return;
            const containerRect = this.$refs.selectedItemsContainer.getBoundingClientRect();
            // Clamp the dragged item's position
            const minX = containerRect.left - this.$refs.draggingItem.offsetWidth;
            const maxX = containerRect.right;
            const clampedX = Math.min(Math.max(event.clientX, minX), maxX);
            this.dragX = clampedX - containerRect.left;
            this.$refs.draggingItem.style.left = this.dragX + 'px';

            const minY = containerRect.top;
            const maxY = containerRect.bottom - this.$refs.draggingItem.offsetHeight;
            const clampedY = Math.min(Math.max(event.clientY, minY), maxY);
            this.dragY = clampedY - containerRect.top;
            this.$refs.draggingItem.style.top = this.dragY + 'px';

            const newIndex = this.findNewIndex();
            if (newIndex !== this.dragIndex) {
                this.value.splice(newIndex, 0, this.value.splice(this.dragIndex, 1)[0]);
                this.dragIndex = newIndex;
            }
        },

        stopDragging() {
            window.removeEventListener('mouseup', this.boundStopDragging);
            window.removeEventListener('mousemove', this.boundMoveItem);
            this.dragging = false;
            this.dragIndex = null;
            this.updateSelectedItemsOrder();
        },

        findNewIndex() {
            const itemsContainer = this.$refs.selectedItemsContainer;
            const draggingItem = this.$refs.draggingItem;
            const itemWidth = draggingItem.offsetWidth;
            const itemHeight = draggingItem.offsetHeight;
            const containerWidth = itemsContainer.clientWidth;
            const numCols = Math.floor(containerWidth / itemWidth);

            // Calculate the X and Y index based on the current dragX and dragY position
            const colIndex = Math.floor(this.dragX / itemWidth);
            const rowIndex = Math.floor(this.dragY / itemHeight);

            // Calculate the new index based on the flex structure
            let newIndex = rowIndex * numCols + colIndex;

            // Clamp the new index within the valid range
            newIndex = Math.max(0, Math.min(newIndex, this.items.length - 1));

            return newIndex;
        }
    }" @keydown.down.stop.prevent="focusNext"
        @keydown.right.stop.prevent="focusNext" @keydown.up.stop.prevent="focusPrevious"
        @keydown.left.stop.prevent="focusPrevious" @keydown.escape.stop.prevent="toggleListbox"
        @click.away="closeListbox()">

        <div class="relative p-0 w-full bg-transparent border-0 aura-input">
            <label class="sr-only">{{ __('Select Entry') }}</label>
            <button type="button"
                class="relative flex items-center justify-between w-full px-3 pt-1 pb-0 border rounded-lg shadow-xs appearance-none min-h-[2.625rem] border-gray-500/30 focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700"
                x-ref="listboxButton" @click="toggleListbox">

                <div class="flex items-center w-full">
                    <!-- Placeholder text on the left -->
                    <template x-if="!value || value.length === 0">
                        <span class="block text-sm text-gray-500 truncate dark:text-gray-400">{{ __('Select Entry') }}</span>
                    </template>

                    <!-- Selected items -->
                    <template x-if="value && value.length > 0 && !multiple">
                        <span class="block text-sm truncate" x-html="selectedItemMarkup(value[0]).title || selectedItemMarkup(value[0])"></span>
                    </template>

                    <template x-if="value && value.length > 0 && multiple">
                        <div class="flex flex-wrap items-center pt-0" @mousemove.prevent="moveItem($event)"
                            @mouseup.prevent="stopDragging()" x-ref="selectedItemsContainer">
                            <template x-for="(item, index) in value" :key="item">
                                {{-- <pre x-html="'item: ' + item + ', selected: ' + selectedItemMarkup(item)"></pre> --}}

                                <div class="inline-flex gap-1 items-center py-0.5 pr-2 pl-1 mr-2 mb-1 text-xs font-medium leading-4 rounded-full bg-primary-100 text-primary-800 draggable-selectmany-item"
                                    :data-id="item" @mousedown.prevent="startDragging(index, $event)"
                                    :class="{ 'shadow-item': index == dragIndex }">

                                    <div>
                                        <template x-if="item && selectedItemMarkup(item) && selectedItemMarkup(item).view_selected" :key="item">
                                            <span x-html="selectedItemMarkup(item).view_selected"></span>
                                        </template>

                                        <template x-if="!selectedItemMarkup(item).view_selected" :key="item">
                                            <span class="" x-text="selectedItemMarkup(item).title"></span>
                                        </template>

                                    </div>

                                    <!-- Small x svg -->
                                    <svg @mousedown.prevent="value = value.filter(i => i !== item)"
                                        @click.stop.prevent="value = value.filter(i => i !== item)"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 cursor-pointer text-primary-300">
                                        <path fill-rule="evenodd"
                                            d="M5.293 5.293a1 1 0 011.414 0L10 8.586l3.293-3.293a1 1 0 111.414 1.414L11.414 10l3.293 3.293a1 1 0 01-1.414 1.414L10 11.414l-3.293 3.293a1 1 0 01-1.414-1.414L8.586 10 5.293 6.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </template>

                            <div x-show="dragging"
                                class="inline-flex gap-1 items-center px-2 py-0.5 mr-2 mb-2 text-xs font-medium leading-4 rounded-full bg-primary-100 text-primary-800 dragging-item"
                                x-ref="draggingItem">
                                <template x-if="value[dragIndex] && selectedItemMarkup(value[dragIndex]) && selectedItemMarkup(value[dragIndex]).view_selected" :key="item">
                                    <span x-html="selectedItemMarkup(value[dragIndex]).view_selected"></span>
                                </template>
                                <template x-if="!selectedItemMarkup(value[dragIndex]).view_selected" :key="item">
                                    <span class="" x-text="selectedItemMarkup(value[dragIndex]).title"></span>
                                </template>

                                <!-- Small x svg -->
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                    class="-mr-1 w-4 h-4 cursor-pointer text-primary-300">
                                    <path fill-rule="evenodd"
                                        d="M5.293 5.293a1 1 0 011.414 0L10 8.586l3.293-3.293a1 1 0 111.414 1.414L11.414 10l3.293 3.293a1 1 0 01-1.414 1.414L10 11.414l-3.293 3.293a1 1 0 01-1.414-1.414L8.586 10 5.293 6.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>

                    </template>

                    <!-- Heroicons up/down -->
                    <div class="flex absolute inset-y-0 right-0 items-center px-2 pointer-events-none shrink-0">
                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                        </svg>
                    </div>
                </div>
            </button>

            <template x-if="showListbox">
                <ul x-ref="listbox" x-transition.origin.top x-cloak
                    class="overflow-y-auto absolute left-0 z-10 mt-2 w-full max-h-96 bg-white rounded-md border divide-y shadow-md origin-top outline-none divide-gray-500/30 border-gray-500/30 dark:border-gray-700 dark:bg-gray-900 dark:divide-gray-700">
                    <template x-if="searchable">
                        <li class="border-none">
                            <div>
                                <input x-model.debounce.500ms="search" autofocus x-ref="searchField"
                                    class="px-4 py-2.5 w-full placeholder-gray-500 text-gray-900 border-none focus:ring-primary-600 focus:outline-none"
                                    placeholder="{{ __('Search...')}}">
                            </div>
                        </li>
                    </template>

                    <template x-if="loading">
                        <div role="status" class="mx-auto w-full">
                            <svg aria-hidden="true"
                                class="mx-auto my-8 w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-primary-500"
                                viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                                    fill="currentColor" />
                                <path
                                    d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                                    fill="currentFill" />
                            </svg>
                            <span class="sr-only">Loading...</span>
                        </div>
                    </template>
                    <template x-if="!loading">
                        <template x-for="item in filteredItems" :key="item.id">
                            <li :value="item.id"
                                :class="{
                                    'dark:bg-primary-500/20 dark:hover:bg-primary-500/30 dark:focus:bg-primary-500/30 bg-primary-50 hover:bg-primary-100 focus:outline-none': isSelected(
                                        item),
                                    'dark:bg-gray-900 dark:hover:bg-gray-800 dark:focus:bg-gray-800 bg-white hover:bg-gray-50 focus:bg-gray-100 focus:outline-none':
                                        !isSelected(item),
                                    'opacity-50 cursor-not-allowed': isDisabled(item),
                                }"
                                class="flex gap-2 justify-between items-center px-4 py-2 w-full text-sm transition-colors cursor-pointer focus:ring-primary-600"
                                tabindex="0" role="option" @click="selectItem(item)"
                                @keydown.enter.stop.prevent="selectItem(item)"
                                @keydown.space.stop.prevent="selectItem(item)">
                                <div class="flex items-center space-x-2">
                                    <div>
                                        <template x-if="item.view">
                                            <div x-html="item.view"></div>
                                        </template>
                                        <template x-if="!item.view">
                                            <span x-text="item.title" class="font-semibold"></span>
                                        </template>
                                    </div>
                                    <span x-show="isSelected(item)" class="font-semibold text-primary-600">&check;</span>
                                </div>
                            </li>
                        </template>
                    </template>
                </ul>
            </template>
        </div>

        @if ($create)
            <button
                wire:click="$dispatch('openModal', { component: 'aura::resource-create-modal', arguments: { type: '{{ $field['resource'] }}', params: { 'for': '{{ $field['slug'] }}' } }})"
                class="text-sm cursor-pointer text-bold">
                + {{ __('Create') }}
            </button>
        @endif
    </div>
</x-aura::fields.wrapper>
