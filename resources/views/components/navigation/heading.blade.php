@php
$settings = App\Aura::getOption('team-settings');
@endphp

@php
    if ($settings) {
        $sidebarType = $settings['sidebar-type'] ?? 'primary';
    } else {
        $sidebarType = 'primary';
    }
@endphp
<div>
  <h5 class="px-2 mt-4 text-xs font-semibold tracking-wide uppercase select-none
    @if ($sidebarType == 'primary')
      text-primary-400 dark:text-gray-500
    @elseif ($sidebarType == 'light')
      text-gray-400 dark:text-gray-500
    @elseif ($sidebarType == 'dark')
      text-gray-500
    @endif
  ">{{ $slot }}</h5>
</div>
