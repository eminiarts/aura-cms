@aware(['model', 'form', 'mode'])



@php
    // Create md5 hash of the field array
    $fieldHash = md5(json_encode($field));

@endphp

{{-- @dump($field['fields']) --}}

<div
    x-data="{
        selectedId: 'tab-1',
        currentIndex: 0,
        totalTabs: {{ count($field['fields'] ?? []) }},
        init() {
            // Set the first available tab on the page on page load.
            this.$nextTick(() => this.select(this.$id('tab', 0)))
        },
        select(id, index) {
            this.selectedId = id;
            window.dispatchEvent(new Event('resize'));

            if (index !== undefined){
                this.currentIndex = index;
            }
        },
        isSelected(id) {
            return this.selectedId === id
        },
    }"
    x-id="['tab']"
    class="mx-0 mt-0 w-full"
>

    <ul
        x-ref="tablist"
        @keydown.right.prevent.stop="$focus.wrap().next()"
        @keydown.home.prevent.stop="$focus.first()"
        @keydown.page-up.prevent.stop="$focus.first()"
        @keydown.left.prevent.stop="$focus.wrap().prev()"
        @keydown.end.prevent.stop="$focus.last()"
        @keydown.page-down.prevent.stop="$focus.last()"
        role="tablist"
        class="flex items-stretch px-0 pt-3 mx-0 -mb-px space-x-0"
    >
        @foreach(collect($field['fields'] ?? []) as $key => $tab)
        {{-- if there are no fields, continue --}}
        @if(!optional($tab)['fields'] || !count($tab['fields']))
            @continue
        @endif

        @php
            $tabHasErrors = false;

            foreach($tab['fields'] as $tabField) {
                if($errors->has('form.fields.' . $tabField['slug'])) {
                    $tabHasErrors = true;
                }
            }
        @endphp

        @checkCondition($this->model ?? $model, $tab, $this->form)
            <li >
                <button
                    :id="$id('tab', {{ $key }})"
                    @click="select($el.id, {{ $key }})"
                    @mousedown.prevent
                    @focus="select($el.id, {{ $key }})"
                    type="button"
                    :tabindex="isSelected($el.id) ? 0 : -1"
                    :aria-selected="isSelected($el.id)"
                    :class="isSelected($el.id) ? 'border-primary-600 text-primary-700 dark:border-primary-500 dark:text-primary-500 whitespace-nowrap px-4 border-b-2 font-semibold text-sm' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-500/30 dark:hover:text-gray-500 dark:hover:border-gray-600 whitespace-nowrap px-4 border-b-2 font-semibold text-sm'"
                    class="inline-flex px-4 pb-2.5 {{ $tabHasErrors ? '!text-red-500 border-red-500' : '' }}"
                    role="tab"
                >
                    <span class="tab">{{ __($tab['name']) }}</span>
                    @if($tabHasErrors)
                        <x-aura::icon icon="exclamation" size="sm" class="ml-2" />
                    @endif

                </button>
            </li>
        @endcheckCondition
        @endforeach
    </ul>
    <div role="tabpanels" class="rounded-b-lg border-t border-gray-400/30 dark:border-gray-700">
        @foreach($field['fields'] ?? [] as $key => $field)
        <x-aura::fields.conditions :field="$field" :model="$model">
            <section
                x-show="isSelected($id('tab', {{ $key }}))"
                :aria-labelledby="$id('tab', {{ $key }})"
                role="tabpanel"
                class="py-4 w-full"
            >
                <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" />
            </section>
        </x-aura::fields.conditions>
        @endforeach
    </div>
</div>
