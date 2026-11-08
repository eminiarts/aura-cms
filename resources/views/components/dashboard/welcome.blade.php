@props(['cols' => 'full', 'empty' => false])

@php
    $greeting = match (true) {
        now()->hour < 12 => __('Good morning'),
        now()->hour < 18 => __('Good afternoon'),
        default => __('Good evening'),
    };

    $firstName = \Illuminate\Support\Str::before(trim(auth()->user()->name ?? ''), ' ');
@endphp

{{-- The date is the eyebrow, the greeting the title. An empty workspace gets no extra line here: the
     Recently edited section already says so. ($empty is kept for callers that still pass it.) --}}
<div {{ $attributes->merge(['class' => 'col-span-12']) }}>
    <p class="aura-eyebrow">{{ now()->isoFormat('dddd, LL') }}</p>
    <h1 class="mt-0.5 aura-page-title">
        {{ $greeting }}{{ $firstName ? ', '.$firstName : '' }}
    </h1>
</div>
