@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'compact' => false
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


<a
        @if($route)
            href="{{ route($route, $id ?? null) }}"
        @endif
        {{$attributes->merge([
          'class' => 'group flex items-center rounded-lg transition-colors ease-in-out duration-150' . (Request::fullUrlIs($route ? route($route, $id) : '') ? ' is-active bg-gray-950/5 text-gray-900' : ' text-gray-600 hover:text-gray-900 hover:bg-gray-950/5') . ' ' .  ($compact ? 'sidebar-item-compact px-2 h-8' : 'sidebar-item px-3 h-9'),
          ])}}
>
    <div class="flex items-center ml-0 font-medium {{ $compact ? 'space-x-2 text-sm' : 'space-x-2.5 text-sm' }}">
        {{ $slot }}
    </div>
</a>
