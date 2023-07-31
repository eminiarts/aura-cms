@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'compact' => false
])

@php
$settings = Eminiarts\Aura\Facades\Aura::options()['team-settings'] ?? [];
$sidebarType = $settings['sidebar-type'] ?? 'primary';
@endphp

@php
  $defaultClasses = 'sidebar-item group flex items-center rounded-lg transition ease-in-out duration-150';
  $compactClasses = $compact ? ' sidebar-item-compact px-2 h-8' : ' sidebar-item px-3 h-10';
  
  switch($sidebarType) {
      case 'light':
          $activeClasses = ' is-active bg-gray-200 dark:bg-gray-900 dark:text-white hover:bg-gray-200 text-gray-900';
          $inactiveClasses = ' bg-transparent text-gray-900 dark:text-white dark:hover:bg-gray-900 hover:bg-gray-200';
          break;
      case 'dark':
          $activeClasses = ' is-active bg-gray-900 hover:bg-gray-900 text-white';
          $inactiveClasses = ' bg-transparent hover:bg-gray-900';
          break;
      case 'primary':
      default:
          $activeClasses = ' is-active bg-sidebar-bg-hover hover:bg-sidebar-bg-hover text-white';
          $inactiveClasses = ' bg-transparent dark:hover:bg-gray-900 hover:bg-sidebar-bg-hover';
          break;
  }
@endphp

<a
  @if($route)
  href="{{ $route }}" wire:navigate
  tabindex="0"
  @endif
  {{$attributes->merge([
    'class' => $defaultClasses . (url()->current() == $route ? $activeClasses : $inactiveClasses) . $compactClasses,
    ])}}
>
  <div class="flex items-center ml-0 font-semibold {{ $compact ? 'space-x-2 text-sm' : 'space-x-3 text-base' }}">
    {{ $slot }}
  </div>
</a>
