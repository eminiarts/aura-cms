@props([
    'permission' => false,
    'id' => null,
    'route' => null,
    'strict' => true,
    'compact' => false,
    'tooltip' => false,
])

@php
    $settings = app('aura')::getOption('settings');
    $sidebarType = $settings['sidebar-type'] ?? 'primary';
    $isActive = Request::fullUrlIs($route ? route($route, $id) : '');
@endphp

<x-aura::tippy text="{{ $tooltip }}" position="right">
    <a @if($route) href="{{ route($route, $id ?? null) }}" @endif
       class="aura-sidebar-icon group flex items-center rounded-lg transition ease-in-out duration-150 {{ $compact ? 'sidebar-item-compact px-2 py-1' : 'sidebar-item-icon px-2 py-2' }} {{ $isActive ? 'is-active' : '' }}">
        <div class="flex items-center ml-0 space-x-3 text-base font-semibold">
            <div class="icon-class">
                {{ $slot }}
            </div>
        </div>
    </a>
</x-aura::tippy>
