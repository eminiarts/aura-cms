<div
    x-data="{
        recentUploads: [],
        highlightTimer: null,
        markRecent(ids) {
            // Sequential uploads dispatch one event per file — accumulate.
            this.recentUploads = [...new Set([...this.recentUploads, ...(ids || []).map(String)])];
            clearTimeout(this.highlightTimer);
            this.highlightTimer = setTimeout(() => this.recentUploads = [], 4000);
        }
    }"
    x-on:media-uploaded.window="markRecent($event.detail.ids)"
>
    <div class="grid grid-cols-2 gap-2 my-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 sm:gap-3 md:gap-4 lg:gap-5 sm:my-3 md:my-4 lg:my-5">
        @forelse($rows as $row)
        <div class="relative select-none" wire:key="grid_{{ $row->id }}">
            {{-- In the picker a card click selects AND shows details; on the index it only opens details (the checkbox handles selection). --}}
            <label for="checkbox_{{ $row->id }}" class="block cursor-pointer"
                x-on:click="@if ($field) toggleRow($event, {{ $row->id }}); @endif Livewire.dispatch('open-attachment-details', { id: {{ $row->id }}, ids: rows.map(Number) })"
                data-attachment-card="{{ $row->id }}">
                <div class="relative">
                    <div class="overflow-hidden relative w-full bg-gray-100 rounded-lg shadow-sm transition-all duration-300 ease-in-out dark:bg-gray-800 group aspect-w-10 aspect-h-7 hover:shadow-md focus-within:ring-2 focus-within:ring-primary-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-100"
                        :class="{
                            'shadow-[inset_0_0_0_4px_theme(colors.primary.500)]': selected.includes('{{ $row->id }}'),
                            'opacity-50': maxFilesReached && !selected.includes('{{ $row->id }}'),
                            'ring-2 ring-green-400 ring-offset-2 dark:ring-offset-gray-900': recentUploads.includes('{{ $row->id }}')
                        }">
                        @include('aura::attachment.thumbnail')
                        <div class="rounded-lg absolute inset-0 opacity-0 shadow-[inset_0_0_0_4px_theme(colors.primary.500)]"
                             :class="{ 'opacity-100': selected.includes('{{ $row->id }}') }"></div>
                    </div>

                    {{-- Freshly uploaded badge --}}
                    <div x-cloak x-show="recentUploads.includes('{{ $row->id }}')" x-transition.opacity.duration.300ms
                        class="flex absolute top-3 right-3 justify-center items-center w-6 h-6 text-white bg-green-500 rounded-full shadow"
                        data-uploaded-badge>
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                    </div>

                    <div class="absolute top-3 left-3">
                        <x-aura::input.checkbox
                            id="checkbox_grid_{{ $row->id }}"
                            x-bind:checked="selected.includes('{{ $row->id }}')"
                            :value="$row->id"
                            x-bind:class="{
                                'opacity-0 group-hover:opacity-100': !selected.includes('{{ $row->id }}'),
                                'opacity-100': selected.includes('{{ $row->id }}')
                            }"
                            x-bind:disabled="maxFilesReached && !selected.includes('{{ $row->id }}')"
                            x-on:click.stop="toggleRow($event, {{ $row->id }})"
                        />
                    </div>
                </div>
            </label>

            <div class="px-1 mt-2">
                <p class="text-sm font-medium text-gray-900 truncate dark:text-gray-100" title="{{ $row->name }}">
                    {{ $row->name }}
                </p>
                <div class="flex justify-between items-center mt-1">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $row->readable_mime_type }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $row->readable_filesize }}
                    </p>
                </div>
            </div>
        </div>
        @empty
            <div class="col-span-full">
                <div class="py-12 mx-auto text-center">
                    <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                    </svg>
                    <h3 class="mt-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('No media yet') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Drag files anywhere on this page or use the Upload Files button.') }}</p>
                </div>
            </div>
        @endforelse
    </div>

    {{ $rows->links() }}

    <div>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ count($selected) }} {{ __('selected') }}

            @if(optional($field)['max_files'])
                <span>
                    ({{ __('Max') }}: {{ $field['max_files'] }})
                </span>
            @endif
        </p>
    </div>
</div>
