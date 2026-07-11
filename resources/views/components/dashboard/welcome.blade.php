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
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">
                {{ $greeting }}{{ $firstName ? ', '.$firstName : '' }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ now()->translatedFormat('l, j. F Y') }} &middot; {{ __("Here's what's happening in your CMS.") }}
            </p>
        </div>
    </div>
</div>
