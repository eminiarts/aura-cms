@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'compact' => false,
  'tooltip' => false,
])

@php
$settings = Eminiarts\Aura\Aura::getOption('team-settings');
@endphp

@php
    if ($settings) {
        $sidebarType = $settings['sidebar-type'] ?? 'primary';
    } else {
        $sidebarType = 'primary';
    }

    if ($sidebarType == 'primary') {
        $iconClass = 'group-[.is-active]:text-white text-primary-300 dark:text-primary-500 group-hover:text-primary-200 dark:group-hover:text-primary-600';
    } else if ($sidebarType == 'light') {
        $iconClass = 'group-[.is-active]:text-primary-500 text-primary-500 dark:text-primary-500 group-hover:text-primary-500';
    } else if ($sidebarType == 'dark') {
        $iconClass = 'group-[.is-active]:text-primary-500 text-primary-500';
    }


@endphp

{{-- {{ dump(route($route, $id)) }}
@dump(Request::fullUrlIs(route($route, $id))) --}}

@if ($sidebarType == 'primary')
<x-aura::tippy text="{{ $tooltip }}" position="right">
  <a
    @if($route)
    href="{{ route($route, $id ?? null) }}"
    @endif
    {{$attributes->merge([
      'class' => 'group flex items-center py-2 px-aura::2 rounded-lg transition ease-in-out duration-150' . (Request::fullUrlIs($route ? route($route, $id) : '') ? ' is-active bg-primary-600 hover:bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 hover:bg-primary-600') . ' ' .  ($compact ? 'px-aura::2 py-1' : 'px-aura::2 py-2'),
      ])}}
  >
    <div class="flex items-center ml-0 space-x-aura::3 text-base font-semibold">
      <div class="{{ $iconClass }}">
        {{ $slot }}
      </div>
    </div>
  </a>
</x-aura::tippy>

@elseif ($sidebarType == 'light')
<x-aura::tippy text="{{ $tooltip }}" position="right">
  <a
    @if($route)
    href="{{ route($route, $id ?? null) }}"
    @endif
    {{$attributes->merge([
      'class' => 'group flex items-center py-2 px-aura::2 rounded-lg transition ease-in-out duration-150' . (Request::fullUrlIs($route ? route($route, $id) : '') ? ' is-active bg-gray-200 dark:bg-gray-900 dark:text-white hover:bg-gray-100 text-gray-900' : ' bg-gray-50 text-gray-900 dark:text-white dark:bg-gray-800 hover:bg-gray-200') . ' ' .  ($compact ? 'px-aura::2 py-1' : 'px-aura::2 py-2'),
      ])}}
  >
    <div class="flex items-center ml-0 space-x-aura::3 text-base font-semibold">
      <div class="{{ $iconClass }}">
        {{ $slot }}
      </div>
    </div>
  </a>
</x-aura::tippy>

@elseif ($sidebarType == 'dark')
<x-aura::tippy text="{{ $tooltip }}" position="right">
  <a
    @if($route)
    href="{{ route($route, $id ?? null) }}"
    @endif
    {{$attributes->merge([
      'class' => 'group flex items-center py-2 px-aura::2 rounded-lg transition ease-in-out duration-150' . (Request::fullUrlIs($route ? route($route, $id) : '') ? ' is-active bg-gray-900 hover:bg-gray-900 text-white' : ' bg-gray-800 dark:bg-gray-800 hover:bg-gray-900') . ' ' .  ($compact ? 'px-aura::2 py-1' : 'px-aura::2 py-2'),
      ])}}
  >
    <div class="flex items-center ml-0 space-x-aura::3 text-base font-semibold">
      <div class="{{ $iconClass }}">
        {{ $slot }}
      </div>
    </div>
  </a>
</x-aura::tippy>

@endif
