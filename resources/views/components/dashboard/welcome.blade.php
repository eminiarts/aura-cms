@props(['cols' => 'full', 'empty' => false])

@php
    $greeting = match (true) {
        now()->hour < 12 => __('Good morning'),
        now()->hour < 18 => __('Good afternoon'),
        default => __('Good evening'),
    };

    $firstName = \Illuminate\Support\Str::before(trim(auth()->user()->name ?? ''), ' ');
@endphp

{{-- The date is the eyebrow, the greeting the title with the page's bookmark toggle. An empty workspace gets no extra line here: the
     Recently edited section already says so. ($empty is kept for callers that still pass it.) --}}
<div {{ $attributes->merge(['class' => 'col-span-12']) }}>
    <p class="aura-eyebrow">{{ now()->isoFormat('dddd, LL') }}</p>
    <div class="flex gap-1 items-center mt-0.5 min-w-0">
        <h1 class="aura-page-title">
            {{ $greeting }}{{ $firstName ? ', '.$firstName : '' }}
        </h1>
        <x-aura::page-bookmark />
    </div>
</div>
