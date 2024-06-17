@props([
  'toggled' => false,
  'compact' => false,
])

@php
    $settings = app('aura')::getOption('settings');

@endphp

@php
    if ($settings) {
        $sidebarType = $settings['sidebar-type'] ?? 'primary';
    } else {
        $sidebarType = 'primary';
    }
@endphp
<div class="px-2 mt-4 aura-sidebar-heading">
    <x-aura::tippy text="{{ $slot }}" position="right">
        <div x-cloak class="py-2 -mt-2 mb-2 h-0 show-collapsed">
            <div class="w-full border-b aura-sidebar-heading-border"></div>
        </div>
    </x-aura::tippy>

    <div class="hide-collapsed">
        <div class="flex justify-between items-center w-full">
            <h5 class="{{ $compact ? 'text-2xs font-medium' : 'text-xs font-semibold' }} tracking-wide uppercase select-none">{{ $slot }}</h5>

            @if ($toggled)
            @else
                <span>+</span>
            @endif
        </div>
    </div>
</div>
