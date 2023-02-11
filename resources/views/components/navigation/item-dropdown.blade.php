@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'compact' => false
])

@php
$settings = Eminiarts\Aura\Facades\Aura::getOption('team-settings');
@endphp

@php
    if ($settings) {
        $sidebarType = $settings['sidebar-type'] ?? 'primary';
    } else {
        $sidebarType = 'primary';
    }
@endphp

@if ($sidebarType == 'primary')
<a
  @if($route)
  href="{{ route($route, $id ?? null) }}"
  @endif
  {{$attributes->merge([
    'class' => 'group flex items-center rounded-lg transition ease-in-out duration-150' . (Request::fullUrlIs($route ? route($route, $id) : '') ? ' is-active text-gray-700 border border-white bg-transparent hover:bg-white focus:ring-gray-300 hover:border-gray-400' : 'text-gray-700 border border-white bg-transparent hover:bg-white focus:ring-gray-300 shadow-sm hover:border-gray-400') . ' ' .  ($compact ? 'sidebar-item-compact px-2 h-8' : 'sidebar-item px-3 h-10'),
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
  @endif
  {{$attributes->merge([
    'class' => 'group flex items-center rounded-lg transition ease-in-out duration-150' . (Request::fullUrlIs($route ? route($route, $id) : '') ? ' is-active bg-gray-200 dark:bg-gray-900 dark:text-white hover:bg-gray-200 text-gray-900' : ' bg-gray-50 text-gray-900 dark:text-white dark:bg-gray-800 dark:hover:bg-gray-900 hover:bg-gray-200') . ' ' .  ($compact ? 'sidebar-item-compact px-2 h-8' : 'sidebar-item px-3 h-10'),
    ])}}
>
  <div class="flex items-center ml-0 font-semibold {{ $compact ? 'space-x-2 text-sm' : 'space-x-3 text-base' }}">
    {{ $slot }}
  </div>
</a>

@elseif ($sidebarType == 'dark')

<a
  @if($route)
  href="{{ route($route, $id ?? null) }}"
  @endif
  {{$attributes->merge([
    'class' => 'group flex items-center rounded-lg transition ease-in-out duration-150' . (Request::fullUrlIs($route ? route($route, $id) : '') ? ' is-active bg-gray-900 hover:bg-gray-900 text-white' : ' bg-gray-800 hover:bg-gray-900') . ' ' .  ($compact ? 'sidebar-item-compact px-2 h-8' : 'sidebar-item px-3 h-10'),
    ])}}
>
  <div class="flex items-center ml-0 font-semibold {{ $compact ? 'space-x-2 text-sm' : 'space-x-3 text-base' }}">
    {{ $slot }}
  </div>
</a>

@endif
