{{ app('aura')::injectView('breadcrumbs_before') }}

@php
    // Set by breadcrumbs.li while the slot rendered; cleared so a later trail starts fresh.
    $parent = request()->attributes->get('aura.breadcrumbs.parent');
    request()->attributes->remove('aura.breadcrumbs.parent');
@endphp

{{-- Phones show a single back link to the trail's parent (14px, above the title) instead of the full trail. --}}
@if ($parent)
<a href="{{ $parent['href'] }}" wire:navigate
    class="inline-flex gap-1 items-center -ml-1 max-w-full h-5 text-sm font-medium text-gray-500 rounded-sm transition-colors duration-150 sm:hidden hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-gray-400 dark:hover:text-white"
    data-breadcrumb-back>
    <svg class="size-4 shrink-0" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m10 12-4-4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <span class="truncate">{!! e($parent['title'], false) !!}</span>
</a>
@endif

<nav class="hidden items-center h-5 sm:flex" aria-label="Breadcrumb">
    <ol class="inline-flex gap-x-1.5 items-center">
        {{ $slot }}
    </ol>
    @auth
    <livewire:aura::bookmark-page :site="['title' => url()->current(), 'url' => url()->current()]" />
    @endauth
</nav>

{{ app('aura')::injectView('breadcrumbs_after') }}