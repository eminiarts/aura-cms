@props(['align' => 'right', 'width' => 'w-48', 'contentClasses' => 'py-1 bg-white dark:bg-gray-900 dark:text-gray-200', 'closeOnSelect' => true])

@php
switch ($align) {
    case 'left':
        $alignmentClasses = 'origin-top-left left-0';
        break;
    case 'top':
        $alignmentClasses = 'origin-top';
        break;
    case 'right':
    default:
        $alignmentClasses = 'origin-top-right right-0';
        break;
}

switch ($width) {
    case '48':
        $width = 'w-48';
        break;
}
@endphp

<div class="relative" x-data="{
    open: false,
    stopPropagation(e) {
        e.stopPropagation();
    }
}" @click.outside="open = false" @keyup.escape.window="open = false" @close.stop="open = false" x-on:action-confirmed.window="open=false;">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute z-1 mt-2 {{ $width }} rounded-md shadow-lg {{ $alignmentClasses }}"
            style="display: none;"
            @if($closeOnSelect)
            @click="open = false"
            @endif
            >
        <div class="rounded-lg ring-1 ring-black dark:ring-white ring-opacity-5 dark:ring-opacity-10 {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
