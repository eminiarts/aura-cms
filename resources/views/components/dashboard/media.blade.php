@props(['media', 'cols' => 'full'])

{{-- Unboxed strip: hairline title with the link to the library, then one row of square thumbnails. --}}
<div {{ $attributes->merge(['class' => 'col-span-12 min-w-0']) }} data-dashboard-media>
    <div class="flex gap-4 justify-between items-baseline pb-2 border-b border-gray-200 dark:border-white/10">
        <h2 class="aura-section-title">{{ __('Recent uploads') }}</h2>
        <a href="{{ route('aura.attachment.index') }}" wire:navigate
            class="inline-flex shrink-0 gap-1 items-center text-sm font-medium whitespace-nowrap rounded-md transition-colors duration-150 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
            {{ __('Media Library') }}
            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 3.5 10.5 8 6 12.5" />
            </svg>
        </a>
    </div>

    <div class="grid grid-cols-4 gap-3 mt-4 sm:grid-cols-6 lg:grid-cols-8 xl:grid-cols-12">
        @foreach ($media as $file)
            <a href="{{ $file->viewUrl() ?? route('aura.attachment.index') }}" wire:navigate title="{{ $file->name }}"
                class="block overflow-hidden relative rounded-lg ring-1 transition duration-150 aspect-square ring-gray-950/10 dark:ring-white/10 group hover:ring-gray-950/25 dark:hover:ring-white/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                @if ($file->isImage())
                    <img src="{{ $file->path('thumbnail') }}" alt="{{ $file->name }}" loading="lazy" class="object-cover w-full h-full" />
                @else
                    <div class="flex flex-col gap-1 justify-center items-center w-full h-full bg-gray-50 dark:bg-gray-700/50">
                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M14 2.5V7C14 7.55228 14.4477 8 15 8H19.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M19.5 8.5V17C19.5 19.2091 19.2091 21.5 16 21.5H8C4.79086 21.5 4.5 19.2091 4.5 17V7C4.5 4.79086 4.79086 2.5 8 2.5H13.5L19.5 8.5Z" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span class="px-1 text-[10px] font-medium text-gray-500 dark:text-gray-400 truncate max-w-full">{{ $file->readable_mime_type }}</span>
                    </div>
                @endif
            </a>
        @endforeach
    </div>
</div>
