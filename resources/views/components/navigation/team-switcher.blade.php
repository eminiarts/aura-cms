@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'compact' => false,
  'title' => ''
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


<div x-data="{ active: {{ (Request::fullUrlIs($route ? route($route, $id) : '') ? ' 1' : '0')  }}, compact: {{ $compact ? '1' : '0' }} }" class="w-full">

    <div x-data="teamDropdown" >
      <div x-ref="this" role="button" tabindex="0" class="
        flex items-center justify-between w-full cursor-pointer text-sm font-semibold rounded-lg
        @if ($sidebarType == 'primary')
          text-white
          bg-sidebar-bg dark:bg-gray-800 hover:bg-sidebar-bg-hover
          dark:bg-gray-800 dark:hover:bg-gray-900
        @elseif ($sidebarType == 'light')
          text-gray-700
          bg-gray-50 hover:bg-gray-200
          dark:bg-gray-800 dark:hover:bg-gray-900
        @elseif ($sidebarType == 'dark')
          text-white
          bg-gray-800 hover:bg-gray-900
        @endif
      ">
        <span>{{ $title }}</span>

        <div>
          <!-- svg chevron up down -->
          <x-aura::icon.chevron-up class="w-5 h-5" />
        </div>
      </div>

    </div>

</div>

@push('scripts')
    @once
<script nonce="{{ csp_nonce() }}">
  // when alpine is ready
  document.addEventListener('alpine:init', () => {
    // define an alpinejs component named 'teamDropdown'
    Alpine.data('teamDropdown', () => ({
      open: false,
      init() {
        // when the component is initialized, add a click event listener to the document
        this.$nextTick(() => {
          tippy(this.$refs.this, {
            arrow: true,
            theme: 'aura-small',
            trigger: 'click',
            offset: [0, 8],
            placement: 'top-start',
            content: '{!! str_replace("\n", "", $slot) !!}',
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
    }));
  })

</script>

    @endonce
@endpush
