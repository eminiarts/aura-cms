@props([
  'suffix' => false,
  'prefix' => false,
  'size' => 'base',
  'class' => '',
  'label' => false,
])


<div class="w-full">
  @if ($label)
    <label class="inline-block mt-3 mb-2 text-sm font-medium text-aura-text">{{ $label }}</label>
  @endif
  <div class="flex rounded-lg">
    @if ($prefix)
      <span class="inline-flex items-center whitespace-nowrap rounded-l-lg bg-aura-panel text-aura-muted shadow-xs ring-1 ring-aura-border {{ $size === 'xs' ? 'px-2 text-xs' : 'px-3 text-sm' }}">
          {{ $prefix }}
      </span>
    @endif
    <input {{ $attributes->merge(['class' => $class . ' w-full appearance-none border-0 bg-aura-background rounded-none text-aura-text placeholder:text-aura-muted shadow-xs ring-1 ring-aura-border transition duration-150 hover:ring-aura-muted/50 focus:outline-none focus:ring-2 focus:ring-aura-primary disabled:cursor-not-allowed disabled:bg-aura-panel disabled:text-aura-muted disabled:ring-aura-border [color-scheme:light] dark:[color-scheme:dark] z-[1] ' .
    ($prefix ? '' : 'rounded-l-lg ') .
    ($suffix ? '' : 'rounded-r-lg') .
    ($size === 'xs' ? ' px-2 py-1 text-xs' : ' px-3 py-2 text-sm')
    ]) }}/>

    @if ($suffix)
      <span class="inline-flex items-center whitespace-nowrap rounded-r-lg bg-aura-panel text-aura-muted shadow-xs ring-1 ring-aura-border {{ $size === 'xs' ? 'px-2 text-xs' : 'px-3 text-sm' }}">
          {{ $suffix }}
      </span>
    @endif
  </div>
</div>
