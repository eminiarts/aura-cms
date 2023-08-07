@props([
  'text' => 'Tooltip',
  'position' => 'top'
])



<div x-data="userDropdown" x-ref="this">
  {{ $title }}
</div>

@push('scripts')
    @once
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
          placement: '{{ $position }}',
          content: '{!! str_replace("\n", "", $slot) !!}',
          allowHTML: true,
          interactive: true,
        })
      }
    }));
  })

</script>

    @endonce
@endpush
