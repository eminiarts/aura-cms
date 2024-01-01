@props([
  'toggled' => false,
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
<div class="
    px-2 mt-4
  @if ($sidebarType == 'primary')
    text-sidebar-text dark:text-gray-500
  @elseif ($sidebarType == 'light')
    text-gray-400 dark:text-gray-500
  @elseif ($sidebarType == 'dark')
    text-gray-500
  @endif
">
  <x-aura::tippy text="Test" position="right">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="w-4 h-4 show-collapsed">
      <path d="M8 2a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM8 6.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM9.5 12.5a1.5 1.5 0 1 0-3 0 1.5 1.5 0 0 0 3 0Z" />
    </svg>
  </x-aura::tippy>

  <div class="flex justify-between items-center hide-collapsed">
      <h5 class="text-xs font-semibold tracking-wide uppercase select-none">{{ $slot }}</h5>

      @if ($toggled)
      @else
          <span>+</span>
      @endif
  </div>


</div>
