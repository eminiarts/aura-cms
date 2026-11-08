{{-- Titles arrive escaped from title="{{ }}" attributes and raw from :title bindings: escape without double-encoding. --}}
@props([
'href' => null,
'title',
'icon' => 'arrow-right',
'iconClass' => '',
'backTitle' => null,
])

@php
// A quiet trail: 13px medium, links gray-500 (gray-900 on hover), the current page gray-700, so the
// page title below stays the loudest line. Icons are 16px and take the text color; the chevron is
// a 14px gray-300 separator. A passed iconClass still sizes an icon.
$isSeparator = $icon === 'arrow-right';

if (! $iconClass) {
    $iconClass = $isSeparator ? 'w-3.5 h-3.5' : 'w-4 h-4';
}

$iconWrapper = 'flex shrink-0 '.($isSeparator ? 'text-gray-300 dark:text-gray-600' : '');

// The trail's last titled link is the page's parent: the breadcrumbs wrapper (rendered after its
// slot) turns it into the phone back link. The home icon has no title and never becomes the parent.
// backTitle names the link differently on phones (an edit page says "Task", not the record title the H1 repeats).
if ($href && (string) $title !== '') {
    request()->attributes->set('aura.breadcrumbs.parent', ['href' => $href, 'title' => $backTitle ?? $title]);
}
@endphp

<li class="inline-flex items-center gap-x-1.5">
    @if($href)
    <a href="{{ $href }}"
        class="inline-flex items-center gap-x-1.5 text-[13px] font-medium leading-5 text-gray-500 rounded-sm transition-colors duration-150 hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-gray-400 dark:hover:text-white">
        @if ((string) $title === '')
            <span class="sr-only">{{ __('Go to home') }}</span>
        @endif
        <x-aura::icon :icon="$icon" size="xs" :iconClass="$iconClass" :class="$iconWrapper" />
        {!! e($title, false) !!}
    </a>
    @else
    <div class="inline-flex items-center gap-x-1.5">
        <x-aura::icon :icon="$icon" size="xs" :iconClass="$iconClass" :class="$iconWrapper" />
        <span class="text-[13px] font-medium leading-5 text-gray-700 dark:text-gray-300">{!! e($title, false) !!}</span>
    </div>
    @endif
</li>
