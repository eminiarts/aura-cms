@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'tooltip' => false,
  'compact' => false
])

@php
    use Eminiarts\Aura\Facades\Aura;

    $settings = Aura::getOption('team-settings');
    $sidebarType = $settings['sidebar-type'] ?? 'primary';
    // Check if the route exists before using it to prevent RouteNotFoundException
    $isActive = false;

    if (Request::url() == $route) {
        $isActive = true;
    }

    // Define simple functions to return class strings
    $getBaseClasses = fn() => 'group flex items-center rounded-lg transition ease-in-out duration-150';
    $getCompactClasses = fn() => $compact ? 'sidebar-item-compact px-2 h-8' : 'sidebar-item px-3 h-10';

    $getActiveClasses = fn() => $isActive ? 'is-active bg-sidebar-bg-hover hover:bg-sidebar-bg-hover text-white' : 'bg-transparent dark:hover:bg-gray-900 hover:bg-sidebar-bg-hover';

    $getSidebarTypeClasses = function() use ($sidebarType, $isActive) {
        return match($sidebarType) {
            'primary' => $isActive ? 'is-active bg-sidebar-bg-hover hover:bg-sidebar-bg-hover text-white' : 'bg-transparent dark:hover:bg-gray-900 hover:bg-sidebar-bg-hover',
            'light' => $isActive ? 'is-active bg-gray-200 dark:bg-gray-900 dark:text-white hover:bg-gray-200 text-gray-900' : 'bg-transparent text-gray-900 dark:text-white dark:hover:bg-gray-900 hover:bg-gray-200',
            'dark' => $isActive ? 'is-active bg-gray-900 hover:bg-gray-900 text-white' : 'bg-transparent hover:bg-gray-900',
            default => 'bg-transparent'
        };
    };
@endphp


<div class="show-collapsed">
  <x-aura::tippy text="{{ $tooltip }}" position="right">
    <a
      @if($route)
      href="{{ $route }}"
      @endif
      tabindex="{{ $route ? '0' : '' }}"
      @class([
          $getBaseClasses(),
          $getSidebarTypeClasses(),
          $getCompactClasses(),
          $attributes->get('class')
      ])
    >
      <div class="flex items-center ml-0 font-semibold {{ $compact ? 'space-x-2 text-sm' : 'space-x-3 text-base' }}">
        {{ $slot }}
      </div>
    </a>
  </x-aura::tippy>
</div>


<div class="hide-collapsed">
  <a
    @if($route)
    href="{{ $route }}"
    @endif
    tabindex="{{ $route ? '0' : '' }}"
    @class([
        $getBaseClasses(),
        $getSidebarTypeClasses(),
        $getCompactClasses(),
        $attributes->get('class')
    ])
  >
    <div class="flex items-center ml-0 font-semibold {{ $compact ? 'space-x-2 text-sm' : 'space-x-3 text-base' }}">
      {{ $slot }}
    </div>
  </a>
</div>
