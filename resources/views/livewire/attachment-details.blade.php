<div
    x-data="{
        saved: false,
        savedTimer: null,
        copied: false,
        flashSaved() {
            this.saved = true;
            clearTimeout(this.savedTimer);
            this.savedTimer = setTimeout(() => this.saved = false, 1500);
        },
        copyUrl(url) {
            navigator.clipboard.writeText(url).then(() => {
                this.copied = true;
                setTimeout(() => this.copied = false, 1500);
            });
        },
        handleKeys(event) {
            if (! $wire.attachmentId) return;
            if (['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) return;
            if (event.key === 'ArrowRight') $wire.next();
            if (event.key === 'ArrowLeft') $wire.previous();
        }
    }"
    x-on:attachment-details-saved.window="flashSaved()"
    x-on:keydown.window="handleKeys($event)"
    @if ($surface === 'index')
        x-on:keydown.escape.window="if ($wire.attachmentId) $wire.close()"
    @endif
>
    @if ($attachment)
        <div
            @if ($surface === 'index')
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                class="flex fixed top-0 right-0 z-20 flex-col w-full max-w-md h-full bg-white border-l shadow-xl border-gray-400/30 dark:bg-gray-900 dark:border-gray-700"
            @else
                class="flex flex-col h-full bg-white border-l border-gray-400/30 dark:bg-gray-900 dark:border-gray-700"
            @endif
            data-attachment-details
        >
            {{-- Header --}}
            <div class="flex flex-shrink-0 justify-between items-center px-5 border-b border-gray-400/30 dark:border-gray-700 h-[4.5rem]">
                <div class="flex items-center space-x-3 min-w-0">
                    <h3 class="text-lg font-semibold text-gray-900 truncate dark:text-gray-100">
                        {{ __('Details') }}
                    </h3>

                    {{-- Saved indicator --}}
                    <span
                        x-cloak
                        x-show="saved"
                        x-transition.opacity.duration.300ms
                        class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-green-700 bg-green-100 rounded-full dark:text-green-300 dark:bg-green-900/50"
                        data-details-saved
                    >
                        <svg class="mr-1 w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                        {{ __('Saved') }}
                    </span>
                </div>

                <div class="flex items-center space-x-1">
                    {{-- Prev / Next --}}
                    @if (count($rowIds) > 1)
                        <span class="mr-2 text-xs tabular-nums text-gray-500 dark:text-gray-400">
                            {{ ($index = array_search($attachmentId, $rowIds)) !== false ? $index + 1 : '–' }} / {{ count($rowIds) }}
                        </span>
                        <button
                            type="button"
                            wire:click="previous"
                            @disabled(($rowIds[0] ?? null) === $attachmentId)
                            class="p-1.5 text-gray-500 rounded-md hover:text-gray-900 hover:bg-gray-100 disabled:opacity-40 disabled:pointer-events-none dark:text-gray-400 dark:hover:text-gray-100 dark:hover:bg-gray-800"
                            aria-label="{{ __('Previous attachment') }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                        </button>
                        <button
                            type="button"
                            wire:click="next"
                            @disabled(end($rowIds) === $attachmentId)
                            class="p-1.5 text-gray-500 rounded-md hover:text-gray-900 hover:bg-gray-100 disabled:opacity-40 disabled:pointer-events-none dark:text-gray-400 dark:hover:text-gray-100 dark:hover:bg-gray-800"
                            aria-label="{{ __('Next attachment') }}"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                        </button>
                    @endif

                    <button
                        type="button"
                        wire:click="close"
                        class="p-1.5 text-gray-500 rounded-md hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-100 dark:hover:bg-gray-800"
                        aria-label="{{ __('Close details') }}"
                        data-details-close
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <div class="overflow-y-auto flex-1 p-5 space-y-5">

                {{-- Preview --}}
                <div class="overflow-hidden rounded-lg bg-gray-50 dark:bg-gray-800" wire:key="preview-{{ $attachment->id }}">
                    @if ($attachment->isImage())
                        <img
                            src="{{ $attachment->thumbnail('md') }}"
                            alt="{{ $attachment->alt_text ?: $attachment->name }}"
                            class="object-contain mx-auto w-full max-h-72"
                        >
                    @elseif (str_starts_with((string) $attachment->mime_type, 'video/'))
                        <video controls preload="metadata" class="w-full max-h-72" src="{{ $attachment->path() }}"></video>
                    @elseif (str_starts_with((string) $attachment->mime_type, 'audio/'))
                        <div class="p-5">
                            <audio controls class="w-full" src="{{ $attachment->path() }}"></audio>
                        </div>
                    @else
                        <div class="flex justify-center items-center py-12 text-gray-300 dark:text-gray-600">
                            @include('aura::attachment.icon', ['class' => 'h-16 w-16', 'attachment' => $attachment])
                        </div>
                    @endif
                </div>

                {{-- Editable metadata --}}
                <div class="space-y-4">
                    <div>
                        <label for="details-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Title') }}
                        </label>
                        <input
                            id="details-title"
                            type="text"
                            wire:model.live.debounce.600ms="title"
                            class="block mt-1 w-full bg-white rounded-lg border transition appearance-none shadow-xs border-gray-500/30 px-3 py-2 focus:outline-none focus:ring focus:border-primary-300 focus:ring-primary-300 focus:ring-opacity-50 dark:bg-transparent dark:border-gray-700 dark:focus:border-gray-500 dark:focus:ring-primary-500 dark:focus:ring-opacity-50"
                        >
                        @error('title')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="details-alt-text" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Alt Text') }}
                        </label>
                        <textarea
                            id="details-alt-text"
                            rows="2"
                            wire:model.live.debounce.600ms="altText"
                            placeholder="{{ __('Describe this file for screen readers and search engines') }}"
                            class="block mt-1 w-full bg-white rounded-lg border transition appearance-none shadow-xs border-gray-500/30 px-3 py-2 focus:outline-none focus:ring focus:border-primary-300 focus:ring-primary-300 focus:ring-opacity-50 dark:bg-transparent dark:border-gray-700 dark:focus:border-gray-500 dark:focus:ring-primary-500 dark:focus:ring-opacity-50"
                        ></textarea>
                        @error('altText')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Facts --}}
                <dl class="text-sm divide-y divide-gray-400/20 dark:divide-gray-700">
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Uploaded') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ optional($attachment->created_at)->format('M j, Y H:i') }}</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Type') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $attachment->readable_mime_type }}</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Size') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $attachment->readable_filesize }}</dd>
                    </div>
                    @if ($attachment->width && $attachment->height)
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Dimensions') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $attachment->width }} × {{ $attachment->height }} px</dd>
                        </div>
                    @endif
                </dl>

                {{-- File URL --}}
                <div>
                    <label for="details-url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('File URL') }}
                    </label>
                    <div class="flex mt-1 space-x-2">
                        <input
                            id="details-url"
                            type="text"
                            readonly
                            value="{{ $attachment->path() }}"
                            x-on:focus="$event.target.select()"
                            class="block w-full text-sm text-gray-600 bg-gray-50 rounded-lg border appearance-none shadow-xs border-gray-500/30 px-3 py-2 focus:outline-none dark:text-gray-300 dark:bg-gray-800 dark:border-gray-700"
                        >
                        <button
                            type="button"
                            x-on:click="copyUrl(@js($attachment->path()))"
                            class="inline-flex flex-shrink-0 items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white rounded-lg border transition shadow-xs border-gray-500/30 hover:bg-gray-50 dark:text-gray-300 dark:bg-transparent dark:border-gray-700 dark:hover:bg-gray-800"
                            data-details-copy-url
                        >
                            <span x-show="! copied">{{ __('Copy') }}</span>
                            <span x-cloak x-show="copied" class="text-green-600 dark:text-green-400">{{ __('Copied!') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Footer actions --}}
            <div class="flex flex-shrink-0 justify-between items-center px-5 py-4 border-t border-gray-400/30 dark:border-gray-700">
                <a
                    href="{{ $attachment->path() }}"
                    download="{{ $attachment->name }}"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white rounded-lg border transition shadow-xs border-gray-500/30 hover:bg-gray-50 dark:text-gray-300 dark:bg-transparent dark:border-gray-700 dark:hover:bg-gray-800"
                >
                    <svg class="mr-1.5 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    {{ __('Download') }}
                </a>

                @if ($surface === 'index')
                    <button
                        type="button"
                        x-on:click="if (window.confirm(@js(__('Delete this attachment permanently?')))) $wire.deleteAttachment()"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-600 rounded-lg border border-transparent transition hover:bg-red-50 hover:border-red-200 dark:text-red-400 dark:hover:bg-red-900/30 dark:hover:border-red-900"
                        data-details-delete
                    >
                        <svg class="mr-1.5 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L5.772 5.79m13.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                        {{ __('Delete') }}
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
