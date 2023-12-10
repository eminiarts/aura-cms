@props([
'href' => null,
'title',
'icon' => 'arrow-right',
'iconClass' => '',
])

@php
if(!$iconClass){
    $iconClass = ($icon == 'arrow-right' ? 'text-gray-300 w-4 h-4 mr-2' : 'w-4 h-4 mr-2');
}
@endphp

<li class="inline-flex items-center">
    @if($href)
    <a href="{{ $href }}"
        class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
        <span class="sr-only">{{ __('Go to home') }}</span>
        <x-aura::icon :icon="$icon" size="xs" :iconClass="$iconClass" />
        {!! $title !!}
    </a>
    @else
    <div class="flex items-center">
        <x-aura::icon :icon="$icon" size="xs" :iconClass="$iconClass" />
        <span class="ml-1 text-sm font-medium text-gray-400 md:ml-2 dark:text-gray-500">{!! $title !!}</span>
    </div>
    @endif
</li>
