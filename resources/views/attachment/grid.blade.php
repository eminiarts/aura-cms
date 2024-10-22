<div>
    <div
        class="grid grid-cols-2 gap-2 my-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 sm:gap-3 md:gap-4 lg:gap-5 sm:my-3 md:my-4 lg:my-5"
        x-data="{
            selected: @entangle('selected'),
            rows: @js($rows->pluck('id')->toArray()),
            lastSelectedId: null,
            field: @js($field),
            maxFilesReached: false,

            init() {
                this.$watch('selected', (value) => {
                    this.maxFilesReached = this.field.max_files && value.length >= this.field.max_files;
                });
            },
            toggleRow(event, id) {
                if (!this.rows || !Array.isArray(this.rows)) {
                    return;
                }
                this.$nextTick(() => {
                    if (this.field.max_files === 1) {
                        this.selected = [id.toString()];
                    } else if (this.field.max_files && this.selected.length >= this.field.max_files && !this.selected.includes(id.toString())) {
                        // Max files reached, don't add more
                        return;
                    } else if (event.shiftKey && this.lastSelectedId !== null) {
                        const lastIndex = this.rows.indexOf(this.lastSelectedId);
                        const currentIndex = this.rows.indexOf(id);

                        if (lastIndex === -1 || currentIndex === -1) {
                            return;
                        }

                        const start = Math.min(lastIndex, currentIndex);
                        const end = Math.max(lastIndex, currentIndex);
                        const rowsToToggle = this.rows.slice(start, end + 1);

                        // Check if the item at the last index is selected or not
                        const isLastSelected = this.selected.includes(this.lastSelectedId.toString());

                        if (isLastSelected) {
                            // Select all rows in the range
                            this.selected = [...new Set([...this.selected, ...rowsToToggle.map(String)])];
                        } else {
                            // Deselect all rows in the range
                            this.selected = this.selected.filter(row => !rowsToToggle.includes(parseInt(row)));
                        }
                    } else {
                        // Toggle single selection
                        const index = this.selected.indexOf(id.toString());
                        if (index === -1) {
                            this.selected.push(id.toString());
                        } else {
                            this.selected.splice(index, 1);
                        }
                    }

                    this.lastSelectedId = id;
                });
            }
        }"
    >
        @forelse($rows as $row)
        <div class="relative select-none" wire:key="grid_{{ $row->id }}">
            <label for="checkbox_{{ $row->id }}" class="block cursor-pointer" x-on:click="toggleRow($event, {{ $row->id }})">
                <div class="relative">
                    <div class="overflow-hidden relative w-full bg-gray-100 rounded-lg transition-all duration-300 ease-in-out dark:bg-gray-800 group aspect-w-10 aspect-h-7 focus-within:ring-2 focus-within:ring-primary-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-100"
                        :class="{
                            'shadow-[inset_0_0_0_4px_theme(colors.primary.500)]': selected.includes('{{ $row->id }}'),
                            'opacity-50 cursor-not-allowed': maxFilesReached && !selected.includes('{{ $row->id }}')
                        }">
                        @include('aura::attachment.thumbnail')
                        <!-- Add a semi-transparent overlay for selected items -->
                        <div class="rounded-lg absolute inset-0 opacity-0 shadow-[inset_0_0_0_4px_theme(colors.primary.500)]"
                             :class="{ 'opacity-100': selected.includes('{{ $row->id }}') }"></div>
                        <!-- Add an overlay with a message when max files is reached -->
                        <div class="flex absolute inset-0 justify-center items-center bg-gray-900 bg-opacity-75 rounded-lg opacity-0 transition-opacity duration-300"
                             :class="{ 'opacity-100': maxFilesReached && !selected.includes('{{ $row->id }}') }">
                            <p class="text-sm font-medium text-white">Max files reached</p>
                        </div>
                    </div>
                    <div class="absolute top-3 left-3">
                        <x-aura::input.checkbox
                            id="checkbox_{{ $row->id }}"
                            x-model="selected"
                            :value="$row->id"
                            x-bind:class="{
                                'opacity-0 group-hover:opacity-100': !selected.includes('{{ $row->id }}'),
                                'opacity-100': selected.includes('{{ $row->id }}'),
                                'cursor-not-allowed': maxFilesReached && !selected.includes('{{ $row->id }}')
                            }"
                            x-bind:disabled="maxFilesReached && !selected.includes('{{ $row->id }}')"
                            x-on:click.stop="toggleRow($event, {{ $row->id }})"
                        />
                    </div>
                </div>
            </label>

            <div class="px-1 mt-2">
                <p class="text-sm font-medium text-gray-900 truncate dark:text-gray-100" title="{{ $row->title ?? '' }}">
                    {{ $row->title ?? '' }}
                </p>
                <div class="flex justify-between items-center mt-1">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $row->mime_type ?? 'Unknown' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $row->size }}
                    </p>
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
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No entries available</h3>
                </div>
            </div>
        @endforelse
    </div>

    {{ $rows->links() }}

    <div>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ count($selected) }} {{ __('selected') }}
            <span x-show="field.max_files">
                ({{ __('Max') }}: {{ $field['max_files'] }})
            </span>
        </p>
    </div>
</div>
