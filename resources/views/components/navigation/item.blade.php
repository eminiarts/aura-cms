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
    use Aura\Base\Facades\Aura;

    $settings = Aura::getOption('team-settings');
    $sidebarType = $settings['sidebar-type'] ?? 'primary';
    // Check if the route exists before using it to prevent RouteNotFoundException
    $isActive = false;

    if (Request::url() == $route || Request::routeIs($route)) {
        $isActive = true;
    }
    if (Request::url() == $href || Request::routeIs($href)) {
        $isActive = true;
    }
    $currentUrl = Request::url();
    $expectedUrl = Request::getSchemeAndHttpHost() . $href;
    $expectedRoute = Request::getSchemeAndHttpHost() . $route;
    if ($currentUrl == $expectedUrl || $currentUrl == $expectedRoute) {
        $isActive = true;
    }

    // Define simple functions to return class strings
    $getBaseClasses = fn() => 'group cursor-pointer flex items-center rounded-lg transition ease-in-out duration-150';
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
                @if($onclick)
                    onclick="{!! $onclick !!}"
                @endif

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
                    $attributes->get('class')
                ])
        >
            <div class="flex justify-between w-full">
                <div class="flex justify-between items-center ml-0 font-semibold truncate {{ $compact ? 'space-x-2 text-sm' : 'space-x-3 text-base' }}">{{ $slot }}</div>

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
                    <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-xs font-medium {{ $badgeColorClasses[$badgeColor] ?? 'bg-gray-100 text-gray-600' }}">{{ $badge }}</span>
                @endif
            </div>
        </a>
    </div>

</div>
