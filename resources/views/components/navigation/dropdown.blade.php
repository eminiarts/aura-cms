@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'compact' => false,
  'title' => '',
])

@php
    use Eminiarts\Aura\Facades\Aura;

    $settings = Aura::getOption('team-settings');
    $sidebarType = $settings['sidebar-type'] ?? 'primary';
    $isActive = Request::fullUrlIs($route ? route($route, $id) : '');
    $compactClass = $compact ? 'sidebar-item-compact px-2 h-8' : 'sidebar-item px-3 h-10';
    $fontClass = $compact ? 'space-x-2 text-sm' : 'space-x-3 text-base';

    $getDropdownClasses = function($expanded) use ($sidebarType, $isActive) {
        return [
            'primary' => [
                'button' => $expanded ? 'bg-transparent dark:bg-gray-800 hover:bg-sidebar-bg-hover' : 'bg-sidebar-bg dark:bg-gray-800 hover:bg-sidebar-bg-hover',
                'container' => 'bg-sidebar-bg-dropdown/70 dark:bg-gray-700/50 rounded-lg'
            ],
            'light' => [
                'button' => $expanded ? 'bg-transparent text-gray-900 dark:text-white dark:bg-gray-800 dark:hover:bg-gray-900 hover:bg-gray-200' : 'bg-gray-50 text-gray-900 dark:text-white dark:bg-gray-800 dark:hover:bg-gray-900 hover:bg-gray-200',
                'container' => 'bg-gray-200/50 dark:bg-gray-700/50 rounded-lg'
            ],
            'dark' => [
                'button' => $expanded ? 'bg-transparent hover:bg-gray-900' : 'bg-gray-800 hover:bg-gray-900',
                'container' => 'bg-gray-700/50 rounded-lg'
            ],
            'default' => [
                'button' => 'bg-transparent',
                'container' => 'bg-transparent rounded-lg'
            ]
        ][$sidebarType] ?? [
            'button' => 'bg-transparent',
            'container' => 'bg-transparent rounded-lg'
        ];
    };
@endphp

<div>

    <div x-data="{ expanded: {{ $isActive ? 'true' : 'false' }}, compact: {{ $compact ? 'true' : 'false' }} }" class="hide-collapsed">
        <div x-data="{
            init() {
                if (this.$refs.container?.querySelector('.is-active')) {
                    this.expanded = true;
                }
            }
        }" role="region" :class="{ '{{ $getDropdownClasses(true)['container'] }}': expanded }">
            <button
                x-on:click="expanded = !expanded"
                :aria-expanded="expanded.toString()"
                class="{{ $compactClass }} flex justify-between items-center w-full rounded-lg transition duration-150 ease-in-out"
                :class="expanded ? '{{ $getDropdownClasses(true)['button'] }}' : '{{ $getDropdownClasses(false)['button'] }}'"
            >
                <div class="flex items-center ml-0 font-semibold {{ $fontClass }}">
                {{ $title }}
                </div>

                <span x-cloak x-show="expanded" aria-hidden="true" class="ml-4">
                <x-aura::icon.chevron-up class="w-6 h-6" />
                </span>
                <span x-cloak x-show="!expanded" aria-hidden="true" class="ml-4">
                <x-aura::icon.chevron-down class="w-6 h-6" />
                </span>
            </button>

            <div x-show="expanded" x-ref="container" x-cloak class="p-2" x-aura::collapse>
                {{ $slot }}
            </div>
        </div>
    </div>

    @php
        $settings = Eminiarts\Aura\Facades\Aura::getOption('team-settings');

        if ($settings) {
            $sidebarType = $settings['sidebar-type'] ?? 'primary';
        } else {
            $sidebarType = 'primary';
        }
    @endphp


    <div x-data="{ active: {{ (Request::fullUrlIs($route ? route($route, $id) : '') ? ' 1' : '0')  }}, compact: {{ $compact ? '1' : '0' }} }" class="show-collapsed">
        {{-- <div x-data="userDropdown{{$id}}"></div> --}}
        <div  x-init="tippy($refs.this, {
            arrow: false,
            theme: 'aura',
            offset: [0, 8],
            placement: 'right',
            content: @js((string) $mobile),
            allowHTML: true,
            interactive: true,
            })" x-ref="this">
        <button
            class="flex items-center justify-between w-full px-2 py-2 transition duration-150 ease-in-out rounded-lg
            @if ($sidebarType == 'primary')
            group-[.is-active]:text-white text-primary-300 hover:text-primary-200 hover:bg-sidebar-bg-hover
            dark:text-primary-500 dark:hover:text-primary-500
            @elseif ($sidebarType == 'light')
            group-[.is-active]:text-primary-500 text-primary-500 hover:text-primary-400
            dark:text-primary-500 group-hover:text-primary-500
            @elseif ($sidebarType == 'dark')
            group-[.is-active]:text-primary-500 text-primary-500 hover:text-primary-400
            @endif
            "
        >
            <div class="flex items-center ml-0 space-x-3 text-base font-semibold">
                <div class="flex items-center ml-0 space-x-3 text-base font-semibold">
                    <div class="sidebar-item-icon">
                    {{ $title }}
                    </div>
                </div>
            </div>
        </button>
        </div>
    </div>


</div>
