@props([
  'permission' => false,
  'id' => null,
  'route' => null,
  'strict' => true,
  'compact' => false
])

{{-- {{ dump(route($route, $id)) }}
@dump(Request::fullUrlIs(route($route, $id))) --}}


<div x-data="{ active: {{ (Request::fullUrlIs($route ? route($route, $id) : '') ? ' 1' : '0')  }}, compact: {{ $compact ? '1' : '0' }} }" class="">

    <div x-data="userDropdown" x-ref="this">

      <button
          class="flex items-center justify-between w-full px-3 py-2 transition duration-150 ease-in-out rounded"
      >

          <div class="flex items-center ml-0 space-x-3 text-base font-semibold">

              <div class="flex items-center ml-0 space-x-3 text-base font-semibold">
                <div class="group-[.is-active]:text-white text-primary-300 group-hover:text-primary-200">
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
    Alpine.data('userDropdown', () => ({
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
