@props([
  'text' => 'Tooltip',
  'position' => 'top'
])

<div x-data="{
   open: false,
      init() {
        tippy(this.$refs.this, {
          arrow: true,
          theme: 'aura',
          offset: [0, 8],
          placement: '{{ $position }}',
          content: @js((string)$slot),
          allowHTML: true,
          interactive: true,
        })
      }
}" x-ref="this">
  {{ $title }}
</div>
