@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'tooltip' => false,
  'compact' => false,
  'badge' => false,
  'badgeColor' => 'primary',
  'onclick' => false,
  'href' => null
])

@php
    $settings = app('aura')::getOption('settings');
    $sidebarType = $settings['sidebar-type'] ?? 'primary';
    // Livewire updates (toggling a nav group) re-render this item on the update endpoint,
    // so resolve the URL and route name of the page the user is on.
    $currentUrl = Request::url();
    $currentRouteName = Request::route()?->getName();
    if (app('livewire')->isLivewireRequest()) {
        $currentUrl = app('livewire')->originalUrl();
        try {
            $currentRouteName = app('router')->getRoutes()->match(Request::create($currentUrl))->getName();
        } catch (\Throwable) {
            $currentRouteName = null;
        }
    }
    $matchesCurrent = fn ($target) => is_string($target) && $target !== '' && (
        $currentUrl === $target
        || ($currentRouteName && Str::is($target, $currentRouteName))
        || $currentUrl === Request::getSchemeAndHttpHost().$target
    );

    $isActive = $matchesCurrent($route) || $matchesCurrent($href);

    // A resource's view, edit and create pages belong to its index item.
    if (! $isActive && $currentRouteName && preg_match('/^(.+)\.(view|edit|create)$/', $currentRouteName, $routeMatch) && Route::has($routeMatch[1].'.index')) {
        $indexUrl = route($routeMatch[1].'.index');
        $isActive = in_array($route, [$routeMatch[1].'.index', $indexUrl], true) || ($href && $href === $indexUrl);
    }

    // Normalize badge color (a null prop should fall back to the default)
    $badgeColor = $badgeColor ?: 'primary';

    // Define simple functions to return class strings
    $getBaseClasses = fn() => 'aura-sidebar-item group';

    $getCompactClasses = fn() => $compact ? 'sidebar-item-compact px-2 h-8' : 'sidebar-item px-3 h-9';

    $getCompactClassesIcon = fn() => $compact ? 'sidebar-item-compact flex justify-center h-8' : 'sidebar-item flex justify-center h-9';

    $getActiveClasses = fn() => $isActive ? 'is-active' : '';

    $getSidebarTypeClasses = function() use ($sidebarType, $isActive) {
        return '';
    };
@endphp


<div>
    <div class="show-collapsed">
        <x-aura::tippy text="{{ $tooltip }}" position="right">
            <a
                @if($onclick)
                    onclick="{!! $onclick !!}"
                @endif

                @if(Route::has($route))
                    href="{{ route($route) }}" wire:navigate
                @elseif($href)
                    href="{{ $href }}" wire:navigate
                @elseif($route)
                    href="{{ $route }}" wire:navigate
                @endif
                tabindex="{{ $route ? '0' : '' }}"

                @class([
                    $getBaseClasses(),
                    $getSidebarTypeClasses(),
                    $getCompactClassesIcon(),
                    $getActiveClasses(),
                    $attributes->get('class')
                ])
            >
                <div class="flex items-center ml-0 font-medium {{ $compact ? 'space-x-2 text-[13px]' : 'space-x-2.5 text-[13px]' }}">
                    {{ $slot }}
                </div>
            </a>
        </x-aura::tippy>
    </div>


    <div class="hide-collapsed">
        <a
                @if(Route::has($route))
                    href="{{ route($route) }}" wire:navigate
                @elseif ($route)
                    href="{{ $route }}" wire:navigate
                @elseif($href)
                    href="{{ $href }}" wire:navigate
                @endif

                tabindex="{{ $route ? '0' : '' }}"

                @class([
                    $getBaseClasses(),
                    $getSidebarTypeClasses(),
                    $getCompactClasses(),
                    $getActiveClasses(),
                    $attributes->get('class')
                ])

                @if($onclick)
                    onclick="{!! $onclick !!}"
                @endif
        >
            <div class="flex justify-between items-center w-full">
                <div class="flex justify-between items-center ml-0 font-medium truncate {{ $compact ? 'space-x-2 text-[13px]' : 'space-x-2.5 text-[13px]' }}">{{ $slot }}</div>

                @php
                    $badgeColorClasses = [
                        'primary' => 'bg-primary-100 text-primary-700',
                        'gray' => 'bg-gray-100 text-gray-600',
                        'red' => 'bg-red-100 text-red-700',
                        'yellow' => 'bg-yellow-100 text-yellow-800',
                        'green' => 'bg-green-100 text-green-700',
                        'blue' => 'bg-blue-100 text-blue-700',
                        'indigo' => 'bg-indigo-100 text-indigo-700',
                        'purple' => 'bg-purple-100 text-purple-700',
                        'pink' => 'bg-pink-100 text-pink-700',
                    ];
                @endphp
                @if($badge)
                    <span class="aura-sidebar-badge aura-sidebar-badge-{{ $badgeColor }} inline-flex shrink-0 items-center justify-center rounded-full px-1.5 py-0.5 ml-2 min-w-[1.25rem] text-xs font-medium leading-4 tabular-nums {{ $badgeColorClasses[$badgeColor] ?? 'bg-gray-100 text-gray-600' }}">{{ $badge }}</span>
                @endif
            </div>
        </a>
    </div>

</div>
