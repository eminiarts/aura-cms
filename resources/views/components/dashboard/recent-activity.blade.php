@props(['items'])

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white dark:bg-gray-800 shadow-xs ring-1 ring-gray-950/10 dark:ring-white/10']) }}>
    <div class="flex flex-col p-6 h-full">
        <div>
            <h2 class="aura-section-title">{{ __('Recently edited') }}</h2>
            <p class="mt-0.5 aura-meta">{{ __('Pick up where you left off') }}</p>
        </div>

        @if (count($items))
            <div class="mt-4 -mx-3 space-y-0.5">
                @foreach ($items as $item)
                    <a href="{{ $item['url'] }}" wire:navigate
                        class="flex gap-3 items-center px-3 py-2 rounded-lg transition-colors duration-150 group hover:bg-gray-50 dark:hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        @if (! empty($item['avatar']))
                            {!! $item['avatar'] !!}
                        @else
                            <span class="flex justify-center items-center w-7 h-7 text-gray-400 rounded-md shrink-0 bg-gray-950/[0.04] dark:bg-white/5 dark:text-gray-500 [&>svg]:size-4" aria-hidden="true">
                                {!! $item['icon'] !!}
                            </span>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate dark:text-white">{{ $item['title'] }}</p>
                            <p class="aura-meta">
                                {{ $item['resource'] }}
                                @if ($item['updated_at'])
                                    &middot; {{ $item['updated_at']->diffForHumans() }}
                                @endif
                            </p>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 transition-colors duration-150 dark:text-gray-600 group-hover:text-gray-500 dark:group-hover:text-gray-400 shrink-0"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                @endforeach
            </div>
        @else
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('Nothing edited yet. Records you create or edit show up here.') }}</p>
        @endif
    </div>
</div>
