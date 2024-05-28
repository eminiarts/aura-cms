@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'compact' => false,
  'title' => ''
])

@php
    $settings = app('aura')::getOption('team-settings');
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
        // when the component is initialized, add a click event listener to the document
        this.$nextTick(() => {
          tippy(this.$refs.this, {
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
        });
      }
    }">
        <div x-ref="this" role="button" tabindex="0" class="flex justify-between items-center w-full text-sm font-semibold rounded-lg cursor-pointer aura-sidebar-team-switcher">
            <span>{{ $title }}</span>

            <div class="hide-collapsed">
                <!-- svg chevron up down -->
                <x-aura::icon.chevron-up class="w-5 h-5"/>
            </div>
        </div>

    </div>

</div>
