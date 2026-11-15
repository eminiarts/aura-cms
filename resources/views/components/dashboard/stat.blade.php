{{-- One dashboard stat card: label and 16px icon, the 28px value, then one 12px footer line. The count
     cards (dashboard.stats) and an app's own dashboard sections (the dashboard_stats slot) share it.
     Props: label, value (string or HtmlString, e.g. a number with its unit in .aura-num-unit), url (the
     card links there), icon (svg markup), caption (the footer line), tone ("danger" colors the value
     red-700 / red-400, for counts that need attention; anything else keeps gray-900).
     Slots: trend (beside the value, e.g. a sparkline), footer (replaces the caption line).
     Place it in a grid; the card brings no column span of its own. --}}
@props(['label', 'value' => null, 'url' => null, 'icon' => null, 'caption' => null, 'tone' => null])

@php
    $tag = $url ? 'a' : 'div';
    $classes = 'flex relative flex-col p-4 min-w-0 bg-white rounded-panel ring-1 sm:p-5 dark:bg-gray-800 ring-aura-edge group'
        .($url ? ' transition duration-150 hover:ring-gray-950/20 dark:hover:ring-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500' : '');
    $valueColor = $tone === 'danger' ? 'text-red-700 dark:text-red-400' : 'text-gray-900 dark:text-white';
@endphp

<{{ $tag }} @if ($url) href="{{ $url }}" wire:navigate @endif {{ $attributes->merge(['class' => $classes]) }} data-stat-card>
    <div class="flex gap-3 justify-between items-center">
        <span class="text-sm font-medium text-gray-500 truncate dark:text-gray-400">{{ $label }}</span>
        @if ($icon)
            <span class="text-gray-400 transition-colors duration-150 dark:text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300 [&>svg]:size-4 shrink-0" aria-hidden="true">
                {!! $icon !!}
            </span>
        @endif
    </div>

    <div class="flex gap-4 justify-between items-end mt-2.5 h-7">
        <span class="aura-stat-value whitespace-nowrap {{ $valueColor }}">{{ $value ?? $slot }}</span>
        {{ $trend ?? '' }}
    </div>

    @if (isset($footer))
        {{ $footer }}
    @elseif (filled($caption))
        <p class="mt-3 text-xs leading-5 text-gray-600 truncate dark:text-gray-300" data-stat-caption>{{ $caption }}</p>
    @endif
</{{ $tag }}>
