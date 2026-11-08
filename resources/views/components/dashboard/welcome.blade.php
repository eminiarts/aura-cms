@props(['cols' => 'full'])

@php
    $greeting = match (true) {
        now()->hour < 12 => __('Good morning'),
        now()->hour < 18 => __('Good afternoon'),
        default => __('Good evening'),
    };

    $firstName = \Illuminate\Support\Str::before(trim(auth()->user()->name ?? ''), ' ');
@endphp

<div {{ $attributes->merge(['class' => 'col-span-12']) }}>
    <div class="flex flex-wrap gap-4 justify-between items-end">
        <div>
            <h1 class="aura-page-title">
                {{ $greeting }}{{ $firstName ? ', '.$firstName : '' }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ now()->isoFormat('dddd, LL') }} &middot; {{ __("Here's what changed in your workspace.") }}
            </p>
        </div>
    </div>
</div>
