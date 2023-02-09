@props([
'header' => null,
'footer' => null,
'slug'
])

<div class="mx-aura::auto max-aura::w-8xl">

    {{-- @dump($this->rows->first()) --}}
    {{-- @dump($this->rows->pluck('id')) --}}
@include('aura::components.table.bulk-select-row')

    <div class="flex flex-aura::col mt-2">
        <div class="min-w-full overflow-hidden overflow-x-aura::auto align-middle border border-gray-400/30 sm:rounded-lg dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
            x-aura::data="{
                selected: @entangle('selected'),
                rows: @js($this->rows->pluck('id')->toArray()), //.map(item => item.toString()),
                lastSelectedId: null,

                init() {
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
                    });
                }

            }"
            >
            {{-- <x-aura::table.header></x-aura::table.header> --}}
            @include('aura::components.table.header')

            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">

                @forelse($this->rows as $row)

                <tr class="bg-white dark:bg-gray-900" wire:key="{{ $row->id }}">

                    <x-aura::table.cell class="pr-0">
                        <x-aura::input.checkbox
                        x-aura::model="selected"
                        :value="$row->id"
                        x-aura::on:click="toggleRow($event, {{ $row->id }})"
                        />
                    </x-aura::table.cell>

                    @include($row->rowView())

                    <td>
                        {{-- @dump($row->editUrl()) --}}
                        {{-- If createInModal is true, open a Modal --}}
                        @if($this->editInModal)
                            <a href="#" wire:click.prevent="$emit('openModal', 'post.edit-modal', {{ json_encode(["post" => $row->id, 'type' => $row->getType()]) }})">
                                <x-aura::icon icon="edit" />
                            </a>
                        @else

                            <x-aura::button.transparent route="aura.post.edit" :id="[$row->getType(), $row->id]" size="xs">
                                <x-slot:icon>
                                    <x-aura::icon icon="edit" size="xs" />
                                </x-slot>
                                Edit
                            </x-aura::button.transparent>

                            {{-- <a href="{{ $row->editUrl() }}">
                                <x-aura::icon icon="edit" />
                            </a> --}}
                        @endif
                    </td>
                </tr>

                @empty

                <tr>
                    <div class="py-8 text-center bg-white dark:bg-gray-900">
                        <svg class="w-12 h-12 mx-aura::auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>

                        <h3 class="mt-2 text-sm font-medium text-gray-900">No entries available</h3>
                    </div>
                </tr>

                @endforelse

            </tbody>
        </table>

    </div>

    <x-aura::table.footer></x-aura::table.footer>

</div>
</div>
