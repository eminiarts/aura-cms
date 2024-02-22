@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'compact' => false,
  'title' => '',
])

@php
    $settings = app('aura')::getOption('team-settings');

    if ($settings) {
        $sidebarType = $settings['sidebar-type'] ?? 'primary';
    } else {
        $sidebarType = 'primary';
    }


    $sidebarType = $settings['sidebar-type'] ?? 'primary';
    $isActive = Request::fullUrlIs($route ? route($route, $id) : '');
    $compactClass = $compact ? 'sidebar-item-compact px-2 h-8' : 'sidebar-item px-3 h-10';
    $fontClass = $compact ? 'space-x-2 text-sm' : 'space-x-3 text-base';
@endphp

<div>

    <div x-data="{ expanded: {{ $isActive ? 'true' : 'false' }}, compact: {{ $compact ? 'true' : 'false' }} }"
         class="hide-collapsed">
        <div x-data="{
            init() {
                if (this.$refs.container?.querySelector('.is-active')) {
                    this.expanded = true;
                }
            }
        }" role="region" :class="{ 'aura-sidebar-dropdown-container-expanded': expanded }">
            <button
                    x-on:click="expanded = !expanded"
                    :aria-expanded="expanded.toString()"
                    class="{{ $compactClass }} flex justify-between items-center w-full rounded-lg transition duration-150 ease-in-out"
                    :class="expanded ? 'aura-sidebar-dropdown-button-expanded' : 'aura-sidebar-dropdown-button'"
            >
                <div class="flex items-center ml-0 font-semibold {{ $fontClass }}">
                    {{ $title }}
                </div>

                <span x-cloak x-show="expanded" aria-hidden="true" class="ml-4">
                <x-aura::icon.chevron-up class="w-6 h-6"/>
                </span>
                <span x-cloak x-show="!expanded" aria-hidden="true" class="ml-4">
                <x-aura::icon.chevron-down class="w-6 h-6"/>
                </span>
            </button>

            <div x-show="expanded" x-ref="container" x-cloak class="p-2" x-aura::collapse>
                {{ $slot }}
            </div>
        </div>
    </div>



    <div x-data="{ active: {{ (Request::fullUrlIs($route ? route($route, $id) : '') ? ' 1' : '0')  }}, compact: {{ $compact ? '1' : '0' }} }"
         class="show-collapsed">
        {{-- <div x-data="userDropdown{{$id}}"></div> --}}
        <div x-init="tippy($refs.this, {
            arrow: false,
            theme: 'aura',
            offset: [0, 8],
            placement: 'right',
            content: @js((string) $mobile),
            allowHTML: true,
            interactive: true,
            })" x-ref="this">
            <button
                    class="flex justify-center items-center py-2 w-full rounded-lg transition duration-150 ease-in-out aura-sidebar-dropdown-collapsed"
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
