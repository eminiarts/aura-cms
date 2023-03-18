<div>

    <div
        class="grid flex-1 grid-cols-5 gap-4 p-4"
        x-data="{
            selected: @entangle('selected'),
            rows: @js($this->rows->pluck('id')->toArray()), //.map(item => item.toString()),
            lastSelectedId: null,

            init() {
            },
            toggleRow(event, id) {
                this.$nextTick(() => {
                    if (event.shiftKey && this.lastSelectedId !== null) {
                        const lastIndex = this.rows.indexOf(this.lastSelectedId);
                        const currentIndex = this.rows.indexOf(id);

                        const start = Math.min(lastIndex, currentIndex);
                        const end = Math.max(lastIndex, currentIndex);

                        if ( (!this.selected.includes(id.toString()) && event.target.tagName !== 'DIV') || (this.selected.includes(id.toString()) && event.target.tagName === 'DIV') ) {
                            this.selected = this.selected.filter(row => !this.rows.slice(start, end + 1).map(item => item.toString()).includes(row.toString()));
                        }
                        else {
                            this.selected = [...this.selected, ...this.rows.slice(start, end + 1)].map(item => item.toString());
                            this.selected = this.selected.filter((item, index) => this.selected.indexOf(item) === index);
                        }
                    }

                    this.lastSelectedId = id;
                });
            }

        }"
    >

        @forelse($this->rows as $row)

        <div class="relative select-none" wire:key="grid_{{ $row->id }}">
            <label for="checkbox_{{ $row->id }}" class="cursor-pointer" x-on:click="toggleRow($event, {{ $row->id }})">
            <div class="block w-full overflow-hidden rounded-lg group aspect-w-10 aspect-h-7 bg-gray-50 focus-within:ring-2 focus-within:ring-primary-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-100">
                @include('aura::attachment.thumbnail')
            </div>
            </label>
            <div class="flex mt-2 space-x-1">
                <div class="shrink-0">
                    <x-aura::input.checkbox
                        id="checkbox_{{ $row->id }}"
                        x-model="selected"
                        :value="$row->id"
                        x-on:click="toggleRow($event, {{ $row->id }})"
                    />
                </div>

                <div class="flex-1 overflow-hidden truncate">
                    <div class="w-full overflow-hidden truncate">
                        <div class="max-w-[10rem]">
                            <p class="block overflow-hidden text-sm font-medium truncate pointer-events-none">{{ $row->title ?? '' }}</p>
                        </div>
                        <p class="block text-sm font-medium text-gray-500 pointer-events-none">{{ $row['fields']['size'] ?? '' }}</p>
                    </div>
                </div>

                <div class="shrink-0">
                    <a href="{{ $row->editUrl() }}">
                        <x-aura::icon icon="edit" />
                    </a>
                </div>
            </div>
        </div>

        @empty

            <div class="col-span-5">
                <div class="py-8 text-center bg-white dark:bg-gray-900 mx-auto">
                <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                    </path>
                </svg>

                <h3 class="mt-2 text-sm font-medium text-gray-900">No entries available</h3>
            </div>
            </div>

        @endforelse

    </div>

    {{ $this->rows->links() }}

</div>

@push('scripts')

{{--<!-- in your Livewire component's JavaScript script -->--}}
{{--<script>--}}
{{--    function selectRange(id) {--}}
{{--        // get the index of the selected item--}}
{{--        const index = this.items.indexOf(id);--}}

{{--        // determine the start and end indices based on the previous and current indices--}}
{{--        const start = Math.min(this.previousIndex, index);--}}
{{--        const end = Math.max(this.previousIndex, index);--}}

{{--        // loop through the items and toggle the selected class for the items between the two clicked items (inclusive)--}}
{{--        for (let i = start; i <= end; i++) {--}}
{{--            this.items[i].classList.toggle('selected');--}}
{{--        }--}}

{{--        this.previousIndex = index;--}}
{{--        this.updateSelected();--}}
{{--    }--}}

{{--    function updateSelected() {--}}
{{--        // update the selected array with the current selected items--}}
{{--        this.selected = this.items.filter(item => item.classList.contains('selected')).map(item => item.dataset.id);--}}

{{--        // emit the updated selected array to Livewire--}}
{{--        this.$emit('updateSelected', this.selected);--}}
{{--    }--}}
{{--</script>--}}

@endpush
