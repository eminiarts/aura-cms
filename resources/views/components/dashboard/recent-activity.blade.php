@props(['items'])

{{-- Unboxed list under a hairline title: avatar, record name, resource, time. Rows open the record. --}}
<div {{ $attributes->merge(['class' => 'min-w-0']) }} data-dashboard-recent>
    <div class="flex justify-between items-baseline pb-2 border-b border-gray-200 dark:border-white/10">
        <h2 class="aura-section-title">{{ __('Recently edited') }}</h2>
    </div>

    @if (count($items))
        <ul class="divide-y divide-gray-100 dark:divide-white/[0.06]">
            @foreach ($items as $item)
                <li>
                    <a href="{{ $item['url'] }}" wire:navigate
                        class="flex gap-3 items-center py-2 -mx-2 px-2 rounded-md transition-colors duration-150 group row-hover:bg-gray-50 dark:row-hover:bg-white/[0.04] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        @if (! empty($item['avatar']))
                            {!! $item['avatar'] !!}
                        @else
                            <span class="flex justify-center items-center w-7 h-7 text-gray-400 rounded-md shrink-0 bg-gray-950/[0.04] dark:bg-white/5 dark:text-gray-500 [&>svg]:size-4" aria-hidden="true">
                                {!! $item['icon'] !!}
                            </span>
                        @endif
                        <span class="flex-1 min-w-0 text-sm font-medium text-gray-900 truncate dark:text-white">{{ $item['title'] }}</span>
                        <span class="hidden text-sm text-gray-500 shrink-0 sm:block dark:text-gray-400">{{ $item['resource'] }}</span>
                        @if ($item['updated_at'])
                            <span class="w-24 text-right shrink-0 aura-meta" title="{{ $item['updated_at']->toDayDateTimeString() }}">{{ $item['updated_at']->diffForHumans(['short' => true]) }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('Nothing edited yet. Records you create or edit show up here.') }}</p>
    @endif
</div>
