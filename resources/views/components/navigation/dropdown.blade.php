@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'compact' => false
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

@if ($sidebarType == 'primary')

<div x-data="{ active: {{ (Request::fullUrlIs($route ? route($route, $id) : '') ? ' 1' : '0')  }}, compact: {{ $compact ? '1' : '0' }} }" class="">
    <div x-data="{
        init() {
            if (this.$refs.container?.querySelector('.is-active')) {
                this.expanded = true;
            }
        },
        get expanded() {
            return this.active === this.id
        },
        set expanded(value) {
            this.active = value ? this.id : null
        },
    }" role="region" :class="{
        'bg-sidebar-bg-dropdown/70 dark:bg-gray-700/50 rounded-lg': expanded,
    }">
          <button
              x-on:click="expanded = !expanded"
              :aria-expanded="expanded"
              class="flex justify-between items-center w-full rounded-lg transition duration-150 ease-in-out"
              :class="{
                  'bg-sidebar-bg dark:bg-gray-800 hover:bg-sidebar-bg-hover': expanded,
                  'bg-sidebar-bg dark:bg-gray-800 hover:bg-sidebar-bg-hover': !expanded,
                  'sidebar-item-compact px-2 h-8': compact,
                  'sidebar-item px-3 h-10': !compact,
              }"
          >

              <div class="flex items-center ml-0 font-semibold {{ $compact ? 'space-x-2 text-sm' : 'space-x-3 text-base' }}">
                {{ $title }}
              </div>

              <span x-cloak x-show="expanded" aria-hidden="true" class="ml-4">
                <x-aura::icon.chevron-up class="w-5 h-5 text-white/60" />
              </span>
              <span x-cloak x-show="!expanded" aria-hidden="true" class="ml-4">
                <x-aura::icon.chevron-down class="w-5 h-5 text-white/60" />
              </span>
          </button>

        <div x-show="expanded" x-ref="container" x-cloak class="p-2" x-aura::collapse>
            {{$slot}}
        </div>
    </div>

</div>

@elseif ($sidebarType == 'light')

<div x-data="{ active: {{ (Request::fullUrlIs($route ? route($route, $id) : '') ? ' 1' : '0')  }}, compact: {{ $compact ? '1' : '0' }} }" class="">
    <div x-data="{
        init() {
            if (this.$refs.container?.querySelector('.is-active')) {
                this.expanded = true;
            }
        },
        get expanded() {
            return this.active === this.id
        },
        set expanded(value) {
            this.active = value ? this.id : null
        },
    }" role="region" :class="{
        'bg-gray-200/50 dark:bg-gray-700/50 rounded-lg': expanded,
    }">
          <button
              x-on:click="expanded = !expanded"
              :aria-expanded="expanded"
              class="flex justify-between items-center w-full rounded-lg transition duration-150 ease-in-out"
              :class="{
                  'bg-gray-50 text-gray-900 dark:text-white dark:bg-gray-800 dark:hover:bg-gray-900 hover:bg-gray-200': expanded,
                  'bg-gray-50 text-gray-900 dark:text-white dark:bg-gray-800 dark:hover:bg-gray-900 hover:bg-gray-200': !expanded,
                  'sidebar-item-compact px-2 h-8': compact,
                  'sidebar-item px-3 h-10': !compact,
              }"
          >

              <div class="flex items-center ml-0 font-semibold {{ $compact ? 'space-x-2 text-sm' : 'space-x-3 text-base' }}">
                {{ $title }}
              </div>

              <span x-show="expanded" x-cloak aria-hidden="true" class="ml-4">
                <x-aura::icon.chevron-up class="w-6 h-6" />
              </span>
              <span x-cloak x-show="!expanded" aria-hidden="true" class="ml-4">
                <x-aura::icon.chevron-down class="w-6 h-6" />
              </span>
          </button>

        <div x-show="expanded" x-ref="container" x-cloak class="p-2" x-aura::collapse>
            {{$slot}}
        </div>
    </div>

</div>


@elseif ($sidebarType == 'dark')

<div x-data="{ active: {{ (Request::fullUrlIs($route ? route($route, $id) : '') ? ' 1' : '0')  }}, compact: {{ $compact ? '1' : '0' }} }" class="">
    <div x-data="{
        init() {
            if (this.$refs.container?.querySelector('.is-active')) {
                this.expanded = true;
            }
        },
        get expanded() {
            return this.active === this.id
        },
        set expanded(value) {
            this.active = value ? this.id : null
        },
    }" role="region" :class="{
        'bg-gray-700/50 rounded-lg': expanded,
    }">
          <button
              x-on:click="expanded = !expanded"
              :aria-expanded="expanded"
              class="flex justify-between items-center w-full rounded-lg transition duration-150 ease-in-out"
              :class="{
                  'bg-gray-800 hover:bg-gray-900': expanded,
                  'bg-gray-800 hover:bg-gray-900': !expanded,
                  'sidebar-item-compact px-2 h-8': compact,
                  'sidebar-item px-3 h-10': !compact,
              }"

          >

              <div class="flex items-center ml-0 font-semibold {{ $compact ? 'space-x-2 text-sm' : 'space-x-3 text-base' }}">
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
            {{$slot}}
        </div>
    </div>

</div>

@endif
