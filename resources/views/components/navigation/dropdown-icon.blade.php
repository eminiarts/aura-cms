@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'compact' => false
])

{{-- {{ dump(route($route, $id)) }}
@dump(Request::fullUrlIs(route($route, $id))) --}}

@php

$id = rand(0, 1000);

$settings = Eminiarts\Aura\Facades\Aura::getOption('team-settings');
@endphp

@php
    if ($settings) {
        $sidebarType = $settings['sidebar-type'] ?? 'primary';
    } else {
        $sidebarType = 'primary';
    }
@endphp


<div x-data="{ active: {{ (Request::fullUrlIs($route ? route($route, $id) : '') ? ' 1' : '0')  }}, compact: {{ $compact ? '1' : '0' }} }" class="">

    <div x-data="userDropdown{{$id}}" x-ref="this">

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
                <div class="sidebar-item-icon ">
                  {{ $title }}
                </div>
              </div>

          </div>

      </button>

    </div>

</div>

<script>
  // when alpine is ready
  document.addEventListener('alpine:init', () => {
    // define an alpinejs component named 'userDropdown'
    Alpine.data('userDropdown{{$id}}', () => ({
      open: false,
      init() {
        // when the component is initialized, add a click event listener to the document
        tippy(this.$refs.this, {
          arrow: true,
          theme: 'aura',
          offset: [0, 8],
          placement: 'right',
          content: '{!! str_replace("\n", "", $slot) !!}',
          allowHTML: true,
          interactive: true,
        })
      }
    }));
  })

</script>
