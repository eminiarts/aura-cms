<div>



    <div class="grid grid-cols-2 gap-2 my-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 sm:gap-3 md:gap-4 lg:gap-5 sm:my-3 md:my-4 lg:my-5">
        @forelse($rows as $row)
        <div class="relative select-none" wire:key="grid_{{ $row->id }}">
            <label for="checkbox_{{ $row->id }}" class="block cursor-pointer" x-on:click="toggleRow($event, {{ $row->id }})">
                <div class="relative">
                    <div class="overflow-hidden relative w-full bg-gray-100 rounded-lg transition-all duration-300 ease-in-out dark:bg-gray-800 group aspect-w-10 aspect-h-7 focus-within:ring-2 focus-within:ring-primary-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-100"
                        :class="{
                            'shadow-[inset_0_0_0_4px_theme(colors.primary.500)]': selected.includes('{{ $row->id }}'),
                            'opacity-50': maxFilesReached && !selected.includes('{{ $row->id }}')
                        }">
                        @include('aura::attachment.thumbnail')
                        <div class="rounded-lg absolute inset-0 opacity-0 shadow-[inset_0_0_0_4px_theme(colors.primary.500)]"
                             :class="{ 'opacity-100': selected.includes('{{ $row->id }}') }"></div>
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

            @if(optional($field)['max_files'])
                <span>
                    ({{ __('Max') }}: {{ $field['max_files'] }})
                </span>
            @endif
        </p>
    </div>
</div>
