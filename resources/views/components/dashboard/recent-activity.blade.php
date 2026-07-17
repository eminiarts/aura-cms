@props(['items'])

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-950/10 dark:ring-white/10']) }}>
    <div class="flex flex-col p-6 h-full">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('Recently edited') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Pick up where you left off') }}</p>
            </div>
            <div class="p-2 rounded-lg bg-primary-50 ring-1 ring-inset ring-primary-600/10 dark:bg-primary-900/50 dark:ring-primary-400/10">
                <svg class="size-5 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
                    <path d="M12 8V12L14.5 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M5.04798 8.60657L2.53784 8.45376C4.33712 3.70477 9.503 0.999914 14.5396 2.34474C19.904 3.77711 23.0904 9.26107 21.6565 14.5935C20.2227 19.926 14.7116 23.0876 9.3472 21.6553C5.36419 20.5917 2.58192 17.2946 2 13.4844" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
        </div>

        @if (count($items))
            <div class="mt-4 -mx-3 space-y-0.5">
                @foreach ($items as $item)
                    <a href="{{ $item['url'] }}" wire:navigate
                        class="flex gap-3 items-center px-3 py-2.5 rounded-lg transition group hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <div class="p-2 text-gray-500 bg-gray-50 rounded-lg dark:bg-gray-700/50 dark:text-gray-400 [&>svg]:w-4 [&>svg]:h-4 shrink-0">
                            {!! $item['icon'] !!}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate dark:text-white">{{ $item['title'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $item['resource'] }}
                                @if ($item['updated_at'])
                                    &middot; {{ $item['updated_at']->diffForHumans() }}
                                @endif
                            </p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 transition dark:text-gray-600 group-hover:text-gray-400 dark:group-hover:text-gray-400 shrink-0"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                @endforeach
            </div>
        @else
            <div class="flex flex-col flex-1 justify-center items-center py-10 text-center">
                <div class="p-3 bg-gray-50 rounded-full dark:bg-gray-700/50">
                    <svg class="w-6 h-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </div>
                <p class="mt-3 text-sm font-medium text-gray-900 dark:text-white">{{ __('Nothing here yet') }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Entries you create or edit will show up here.') }}</p>
            </div>
        @endif
    </div>
</div>
