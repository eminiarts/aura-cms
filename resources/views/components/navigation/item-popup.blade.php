@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'compact' => false
])

@php
    $settings = Aura\Base\Facades\Aura::getOption('team-settings');
@endphp

@php
    if ($settings) {
        $sidebarType = $settings['sidebar-type'] ?? 'primary';
    } else {
        $sidebarType = 'primary';
    }
@endphp


{{-- {{ dump(route($route, $id)) }}
@dump(Request::fullUrlIs(route($route, $id))) --}}
@if ($sidebarType == 'primary')

    <a
            @if($route)
                href="{{ route($route, $id ?? null) }}"
            tabindex="0"
            @endif
            {{$attributes->merge([
              'class' => 'group flex items-center rounded-lg transition ease-in-out duration-150' . (Request::fullUrlIs($route ? route($route, $id) : '') ? ' is-active bg-sidebar-bg-hover hover:bg-sidebar-bg-hover text-white' : ' bg-transparent dark:bg-gray-800 dark:hover:bg-gray-900 hover:bg-sidebar-bg-hover') . ' ' .  ($compact ? 'sidebar-item-compact px-2 h-8' : 'sidebar-item px-3 h-10'),
              ])}}
    >
        <div class="flex items-center ml-0 font-semibold {{ $compact ? 'space-x-2 text-sm' : 'space-x-3 text-base' }}">
            {{ $slot }}
        </div>
    </a>

@elseif ($sidebarType == 'light')
    <a
            @if($route)
                href="{{ route($route, $id ?? null) }}"
            tabindex="0"
            @endif
            {{$attributes->merge([
              'class' => 'sidebar-item group flex items-center rounded-lg transition ease-in-out duration-150' . (Request::fullUrlIs($route ? route($route, $id) : '') ? ' is-active bg-gray-200 dark:bg-gray-900 dark:text-white hover:bg-gray-200 text-gray-900' : ' bg-gray-50 text-gray-900 dark:text-white dark:bg-gray-800 dark:hover:bg-gray-900 hover:bg-gray-200') . ' ' .  ($compact ? 'sidebar-item-compact px-2 h-8' : 'sidebar-item px-3 h-10'),
              ])}}
    >
        <div class="flex items-center ml-0 font-semibold {{ $compact ? 'space-x-2 text-sm' : 'space-x-3 text-base' }}">
            {{ $slot }}
        </div>
    </a>

    {{--
        text-gray-900 border-gray-500/30 border-opacity-20 bg-gray-50 dark:bg-gray-800 dark:text-white dark:border-gray-700 shadow-gray-400 md:shadow-none --}}
@elseif ($sidebarType == 'dark')

    <a
            @if($route)
                href="{{ route($route, $id ?? null) }}"
            tabindex="1"
            @endif
            {{$attributes->merge([
              'class' => 'sidebar-item group flex items-center rounded-lg transition ease-in-out duration-150' . (Request::fullUrlIs($route ? route($route, $id) : '') ? ' is-active bg-gray-900 hover:bg-gray-900 text-white' : ' bg-gray-800 hover:bg-gray-900') . ' ' .  ($compact ? 'sidebar-item-compact px-2 h-8' : 'sidebar-item px-3 h-10'),
              ])}}
    >
        <div class="flex items-center ml-0 font-semibold {{ $compact ? 'space-x-2 text-sm' : 'space-x-3 text-base' }}">
            {{ $slot }}
        </div>
    </a>

    {{--
        text-white border-white border-opacity-20 bg-gray-800 dark:bg-gray-800 dark:text-white dark:border-gray-700 shadow-gray-400 md:shadow-none --}}
@endif
