@props(['media', 'cols' => 'full'])

<div {{ $attributes->merge(['class' => 'col-span-12 rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-950/10 dark:ring-white/10']) }}>
    <div class="p-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Recent uploads') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('The latest files in your media library') }}</p>
            </div>
            <a href="{{ route('aura.attachment.index') }}" wire:navigate
                class="inline-flex gap-1 items-center text-sm font-medium transition text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300">
                {{ __('Media Library') }}
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
            </a>
        </div>

        <div class="grid grid-cols-3 gap-3 mt-4 sm:grid-cols-4 md:grid-cols-6 xl:grid-cols-12">
            @foreach ($media as $file)
                <a href="{{ $file->viewUrl() ?? route('aura.attachment.index') }}" wire:navigate title="{{ $file->name }}"
                    class="block overflow-hidden relative rounded-lg ring-1 transition aspect-square ring-gray-950/10 dark:ring-white/10 group hover:ring-primary-500/50">
                    @if ($file->isImage())
                        <img src="{{ $file->path('thumbnail') }}" alt="{{ $file->name }}" loading="lazy"
                            class="object-cover w-full h-full transition duration-300 group-hover:scale-105" />
                    @else
                        <div class="flex flex-col gap-1 justify-center items-center w-full h-full bg-gray-50 dark:bg-gray-700/50">
                            <svg class="w-6 h-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
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
</div>
