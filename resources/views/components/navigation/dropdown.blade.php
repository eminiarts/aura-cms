@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'compact' => false
])

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

@if ($sidebarType == 'primary')

<div x-aura::data="{ active: {{ (Request::fullUrlIs($route ? route($route, $id) : '') ? ' 1' : '0')  }}, compact: {{ $compact ? '1' : '0' }} }" class="">
    <div x-aura::data="{
        id: 1,
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
        'bg-primary-800/70 rounded-lg': expanded,
    }">
          <button
              x-aura::on:click="expanded = !expanded"
              :aria-expanded="expanded"
              class="flex items-center justify-between w-full transition duration-150 ease-in-out rounded-lg"
              :class="{
                  'bg-primary-700 dark:bg-gray-800 hover:bg-primary-600': expanded,
                  'bg-primary-700 dark:bg-gray-800 hover:bg-primary-600': !expanded,
                  'sidebar-item-compact px-aura::2 h-8': compact,
                  'sidebar-item px-aura::3 h-10': !compact,
              }"
          >

              <div class="flex items-center ml-0 font-semibold {{ $compact ? 'space-x-aura::2 text-sm' : 'space-x-aura::3 text-base' }}">
                {{ $title }}
              </div>

              <span x-aura::cloak x-aura::show="expanded" aria-hidden="true" class="ml-4">
                <x-aura::icon.chevron-up class="w-5 h-5 text-white/60" />
              </span>
              <span x-aura::cloak x-aura::show="!expanded" aria-hidden="true" class="ml-4">
                <x-aura::icon.chevron-down class="w-5 h-5 text-white/60" />
              </span>
          </button>

        <div x-aura::show="expanded" x-aura::cloak class=" p-2" x-aura::collapse>
            {{$slot}}
        </div>
    </div>

</div>

@elseif ($sidebarType == 'light')

<div x-aura::data="{ active: {{ (Request::fullUrlIs($route ? route($route, $id) : '') ? ' 1' : '0')  }}, compact: {{ $compact ? '1' : '0' }} }" class="">
    <div x-aura::data="{
        id: 1,
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
    }" role="region" class="">
          <button
              x-aura::on:click="expanded = !expanded"
              :aria-expanded="expanded"
              class="flex items-center justify-between w-full transition duration-150 ease-in-out rounded-lg"
              :class="{
                  'bg-gray-50 text-gray-900 dark:text-white dark:bg-gray-800 dark:hover:bg-gray-900 hover:bg-gray-200': expanded,
                  'bg-gray-50 text-gray-900 dark:text-white dark:bg-gray-800 dark:hover:bg-gray-900 hover:bg-gray-200': !expanded,
                  'sidebar-item-compact px-aura::2 h-8': compact,
                  'sidebar-item px-aura::3 h-10': !compact,
              }"

          >

              <div class="flex items-center ml-0 font-semibold {{ $compact ? 'space-x-aura::2 text-sm' : 'space-x-aura::3 text-base' }}">
                {{ $title }}
              </div>

              <span x-aura::show="expanded" x-aura::cloak aria-hidden="true" class="ml-4">
                <x-aura::icon.chevron-up class="w-6 h-6" />
              </span>
              <span x-aura::cloak x-aura::show="!expanded" aria-hidden="true" class="ml-4">
                <x-aura::icon.chevron-down class="w-6 h-6" />
              </span>
          </button>

        <div x-aura::show="expanded" x-aura::cloak class="pl-3 ml-[1.4rem] border-l-2 border-l-primary-600" x-aura::collapse>
            {{$slot}}
        </div>
    </div>

</div>


@elseif ($sidebarType == 'dark')
<div x-aura::data="{ active: {{ (Request::fullUrlIs($route ? route($route, $id) : '') ? ' 1' : '0')  }}, compact: {{ $compact ? '1' : '0' }} }" class="">
    <div x-aura::data="{
        id: 1,
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
    }" role="region" class="">
          <button
              x-aura::on:click="expanded = !expanded"
              :aria-expanded="expanded"
              class="flex items-center justify-between w-full transition duration-150 ease-in-out rounded-lg"
              :class="{
                  'bg-gray-800 hover:bg-gray-900': expanded,
                  'bg-gray-800 hover:bg-gray-900': !expanded,
                  'sidebar-item-compact px-aura::2 h-8': compact,
                  'sidebar-item px-aura::3 h-10': !compact,
              }"

          >

              <div class="flex items-center ml-0 font-semibold {{ $compact ? 'space-x-aura::2 text-sm' : 'space-x-aura::3 text-base' }}">
                {{ $title }}
              </div>

              <span x-aura::cloak x-aura::show="expanded" aria-hidden="true" class="ml-4">
                <x-aura::icon.chevron-up class="w-6 h-6" />
              </span>
              <span x-aura::cloak x-aura::show="!expanded" aria-hidden="true" class="ml-4">
                <x-aura::icon.chevron-down class="w-6 h-6" />
              </span>
          </button>

        <div x-aura::show="expanded" x-aura::ref="container" x-aura::cloak class="pl-1 ml-[1.4rem] border-l-2 border-l-gray-600" x-aura::collapse>
            {{$slot}}
        </div>
    </div>

</div>

@endif
