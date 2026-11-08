@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'compact' => false,
  'title' => ''
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


<div x-data="{ active: {{ (Request::fullUrlIs($route ? route($route, $id) : '') ? ' 1' : '0')  }}, compact: {{ $compact ? '1' : '0' }} }"
     class="w-full">

    <div x-data="{
      open: false,
      init() {
        // Tippy is attached on window by the package app.js entry. Alpine may
        // boot before that script finishes evaluating under slow/parallel
        // asset loads — wait for window.tippy instead of throwing ReferenceError.
        this.$nextTick(() => {
          const mount = (attempts = 0) => {
            const tippyFn = window.tippy;

            if (typeof tippyFn !== 'function') {
              if (attempts < 50) {
                setTimeout(() => mount(attempts + 1), 20);
              }

              return;
            }

            tippyFn(this.$refs.this, {
              arrow: false,
              theme: 'aura-small',
              trigger: 'click',
              offset: [0, 8],
              placement: 'top-start',
              content: @js((string)$slot),
              allowHTML: true,
              interactive: true,
              onShow: (instance) => {
                this.open = true;
              },
              onHide: (instance) => {
                this.open = false;
              },
            });

            this.$refs.this.addEventListener('keydown', (event) => {
              if (event.key === ' ' || event.key === 'Enter') {
                event.preventDefault();
                this.$refs.this.click();
              }
            });
          };

          mount();
        });
      }
    }">
        <div x-ref="this" role="button" tabindex="0" class="flex justify-between items-center px-2 py-1.5 w-full text-sm font-medium rounded-lg cursor-pointer aura-sidebar-team-switcher">
            <span class="min-w-0 flex-1">{{ $title }}</span>

            {{-- Up-down selector mark: this opens a menu, it does not expand in place. --}}
            <div class="ml-2 opacity-50 shrink-0 hide-collapsed">
                <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 6 8 3l3 3M5 10l3 3 3-3" />
                </svg>
            </div>
        </div>

    </div>

</div>
