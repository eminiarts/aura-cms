<div
    @if (count($this->recentUploadIds) > 0)
        x-data
        x-init="setTimeout(() => $wire.set('recentUploadIds', []), 8000)"
    @endif
>
    <div class="grid grid-cols-2 gap-2 my-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 sm:gap-3 md:gap-4 lg:gap-5 sm:my-3 md:my-4 lg:my-5">
        @forelse($rows as $row)
        @php
            $isRecentUpload = in_array((string) $row->id, $this->recentUploadIds, true);
        @endphp
        <div class="relative group select-none" wire:key="grid_{{ $row->id }}">
            {{-- Picker: a card click only toggles selection (pure client state, no
                 Livewire roundtrip — mixing both races the entangled selection).
                 Index: a card click opens the Details Panel; the checkbox selects. --}}
            <label for="checkbox_{{ $row->id }}" class="block cursor-pointer"
                x-on:click="@if ($field) toggleRow($event, {{ $row->id }}) @else Livewire.dispatch('open-attachment-details', { id: {{ $row->id }}, ids: rows.map(Number) }) @endif"
                data-attachment-card="{{ $row->id }}">
                <div class="relative">
                    <div class="overflow-hidden relative w-full bg-gray-100 rounded-lg shadow-sm transition-all duration-300 ease-in-out dark:bg-gray-800 aspect-w-10 aspect-h-7 hover:shadow-md focus-within:ring-2 focus-within:ring-primary-500 focus-within:ring-offset-2 focus-within:ring-offset-gray-100 {{ $isRecentUpload ? 'ring-2 ring-green-400 ring-offset-2 dark:ring-offset-gray-900' : '' }}"
                        :class="{
                            'shadow-[inset_0_0_0_4px_theme(colors.primary.500)]': selected.includes('{{ $row->id }}'),
                            'opacity-50': maxFilesReached && !selected.includes('{{ $row->id }}')
                        }">
                        @include('aura::attachment.thumbnail')
                        <div class="rounded-lg absolute inset-0 opacity-0 shadow-[inset_0_0_0_4px_theme(colors.primary.500)]"
                             :class="{ 'opacity-100': selected.includes('{{ $row->id }}') }"></div>
                    </div>

                    @if ($field)
                        {{-- Details trigger in the picker, where the card click selects --}}
                        <button
                            type="button"
                            x-on:click.stop.prevent="Livewire.dispatch('open-attachment-details', { id: {{ $row->id }}, ids: rows.map(Number) })"
                            data-attachment-info="{{ $row->id }}"
                            aria-label="{{ __('Show details') }}"
                            class="flex absolute right-3 bottom-3 justify-center items-center w-7 h-7 text-gray-600 rounded-full shadow-sm opacity-0 transition bg-white/90 hover:bg-white hover:text-gray-900 group-hover:opacity-100 focus-visible:opacity-100 dark:bg-gray-900/80 dark:text-gray-300 dark:hover:text-white"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                        </button>
                    @endif

                    {{-- Freshly uploaded badge (server-rendered so it survives refreshTable) --}}
                    @if ($isRecentUpload)
                        <div class="flex absolute top-3 right-3 justify-center items-center w-6 h-6 text-white bg-green-500 rounded-full shadow"
                            data-uploaded-badge>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                        </div>
                    @endif

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
                <div class="flex flex-col gap-4 justify-center items-center px-6 py-16 mx-auto max-w-md text-center">
                    <div class="flex justify-center items-center w-12 h-12 bg-gray-100 rounded-full dark:bg-gray-800">
                        <x-aura::icon icon="media" class="w-6 h-6 text-gray-400 dark:text-gray-500" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('No media yet') }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Drag and drop your first file anywhere in this window, or upload one directly.') }}
                        </p>
                    </div>
                    <div x-data="{ canUpload: !!document.getElementById('file-upload') }">
                        <x-aura::button.primary
                            x-cloak
                            x-show="canUpload"
                            type="button"
                            class="flex items-center space-x-2"
                            x-on:click="document.getElementById('file-upload').click()">
                            <x-aura::icon.upload class="w-5 h-5" />
                            <span>{{ __('Upload Files') }}</span>
                        </x-aura::button.primary>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    {{ $rows->links() }}

    {{-- Selection count: never shown for single-value fields (the pick itself is
         the feedback), and hidden for everyone while nothing is selected. --}}
    @php($maxFiles = (int) (optional($field)['max_files'] ?? 0))
    @if (count($selected) > 0 && $maxFiles !== 1)
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ count($selected) }} {{ __('selected') }}

                @if($maxFiles > 1)
                    <span class="text-gray-400 dark:text-gray-500">
                        ({{ __('Max') }}: {{ $maxFiles }})
                    </span>
                @endif
            </p>
        </div>
    @endif
</div>
