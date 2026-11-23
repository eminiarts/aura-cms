{{--
    The trailing mark inside a control: one size (16 px), one colour, one inset for every
    select-like field (chevron) and picker (calendar, clock). Place it in a relative wrapper.
--}}
@props(['icon' => 'chevron', 'compact' => false])

<span {{ $attributes->merge(['class' => 'aura-field-indicator pointer-events-none absolute inset-y-0 right-0 flex items-center text-gray-400 dark:text-gray-500 '.($compact ? 'pr-1.5' : 'pr-2.5')]) }} aria-hidden="true">
    @if ($icon === 'calendar')
        <svg class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2.25" y="3.25" width="11.5" height="10.5" rx="1.75" /><path d="M2.25 6.75h11.5M5.25 1.75v3M10.75 1.75v3" stroke-linecap="round" /></svg>
    @elseif ($icon === 'clock')
        <svg class="size-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="5.75" /><path d="M8 5v3.25l2 1.25" stroke-linecap="round" stroke-linejoin="round" /></svg>
    @else
        <svg class="{{ $compact ? 'size-3' : 'size-4' }}" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4.25 6.25 8 10l3.75-3.75" stroke-linecap="round" stroke-linejoin="round" /></svg>
    @endif
</span>
