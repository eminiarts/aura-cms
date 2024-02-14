<div>
    <div
        class="grid flex-1 grid-cols-5 gap-4 my-4"
        x-data="{
            selected: @entangle('selected').live,
            rows: @entangle('rowIds'),
            lastSelectedId: null,

            init() {
                Livewire.on('selectedRows', (updatedSelected) => {
                    console.log('grid uS 2', updatedSelected);
                    {{-- this.selected = updatedSelected[0]; --}}
                });
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
        @forelse($rows as $row)

        <div class="relative select-none" wire:key="grid_{{ $row->id }}">
            <label for="checkbox_{{ $row->id }}" class="cursor-pointer" x-on:click="toggleRow($event, {{ $row->id }})">
            <div class="block overflow-hidden w-full bg-gray-50 rounded-lg group aspect-w-10 aspect-h-7 focus-within:ring-2 focus-within:ring-primary-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-100">
                @include('aura::attachment.thumbnail')
            </div>
            </label>
            <div class="flex mt-2 space-x-1">
                <div class="shrink-0">
                    <x-aura::input.checkbox
                        id="checkbox_{{ $row->id }}"
                        x-model.debounce.150ms="selected"
                        :value="$row->id"
                        x-on:click="toggleRow($event, {{ $row->id }})"
                    />
                </div>

                <div class="overflow-hidden flex-1 truncate">
                    <div class="overflow-hidden w-full truncate">
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
                <div class="py-8 mx-auto text-center bg-white dark:bg-gray-900">
                <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"
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

    {{ $rows->links() }}

    <div>
        @php
        @endphp
        @if(is_int($selected) || is_array($selected))
        {{ count($selected) }} {{ __('selected') }}
        @endif
    </div>

</div>
