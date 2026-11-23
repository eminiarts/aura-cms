@php
    // Class strings copied from fields/tabs.blade.php (resource form tabs).
    $tabBase = 'inline-flex px-4 pb-2.5 whitespace-nowrap border-b-2 font-semibold text-sm';
    $tabActive = 'border-primary-600 text-primary-700 dark:border-primary-500 dark:text-primary-500';
    $tabIdle = 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-500/30 dark:hover:text-gray-500 dark:hover:border-gray-600';
    $tabError = '!text-red-500 border-red-500';

    // Class strings copied from table/filter-tabs.blade.php and table/switch-view.blade.php.
    $segmentActive = 'bg-white text-gray-900 font-medium shadow-xs ring-1 ring-gray-950/[0.07] dark:bg-gray-700 dark:text-white dark:ring-white/10';
    $segmentIdle = 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200';
    $formTabs = ['General', 'Content', 'SEO', 'Advanced'];
@endphp

{{-- Underline tabs (fields/tabs) --}}
<div class="mt-6 aura-card">
    <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Form tabs (fields/tabs)') }}</span>
    <div class="mt-2" x-data="{ selected: 0 }">
        <ul role="tablist" class="flex items-stretch px-0 pt-3 mx-0 -mb-px">
            @foreach ($formTabs as $index => $tab)
                <li>
                    <button type="button" role="tab" x-on:click="selected = {{ $index }}" :aria-selected="selected === {{ $index }}"
                        :class="selected === {{ $index }} ? @js($tabActive) : @js($tabIdle)"
                        class="{{ $tabBase }}">
                        {{ __($tab) }}
                    </button>
                </li>
            @endforeach
        </ul>
        <div class="py-4 text-sm text-gray-500 rounded-b-lg border-t border-gray-400/30 dark:border-gray-700 dark:text-gray-400">
            @foreach ($formTabs as $index => $tab)
                <p x-show="selected === {{ $index }}" @if ($index > 0) x-cloak @endif>{{ __('Fields of the :tab tab.', ['tab' => __($tab)]) }}</p>
            @endforeach
        </div>
    </div>

    <div class="pt-4 mt-2 border-t border-gray-100 dark:border-white/5">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('States') }}</span>
        <ul class="flex flex-wrap items-stretch gap-y-3 mt-3">
            <li class="flex flex-col gap-2 items-start">
                <span class="{{ $tabBase }} {{ $tabActive }}">{{ __('Active') }}</span>
                <code class="px-4 text-[11px] text-gray-400">active</code>
            </li>
            <li class="flex flex-col gap-2 items-start">
                <span class="{{ $tabBase }} {{ $tabIdle }}">{{ __('Idle') }}</span>
                <code class="px-4 text-[11px] text-gray-400">idle</code>
            </li>
            <li class="flex flex-col gap-2 items-start sg-force-hover">
                <span class="{{ $tabBase }} {{ $tabIdle }}">{{ __('Hover') }}</span>
                <code class="px-4 text-[11px] text-gray-400">hover</code>
            </li>
            <li class="flex flex-col gap-2 items-start">
                <span class="{{ $tabBase }} {{ $tabIdle }} {{ $tabError }}">
                    {{ __('Has errors') }}
                    <x-aura::icon icon="exclamation" size="sm" class="ml-2" />
                </span>
                <code class="px-4 text-[11px] text-gray-400">error</code>
            </li>
        </ul>
    </div>
</div>

<div class="grid gap-4 mt-4 lg:grid-cols-2">
    {{-- Segmented tabs (table/filter-tabs) --}}
    <div class="aura-card" x-data="{ selected: 0 }">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Segmented tabs (table/filter-tabs)') }}</span>
        <div class="flex flex-wrap gap-1 items-center p-1 mt-4 max-w-full rounded-lg bg-gray-950/[0.04] dark:bg-white/5 w-fit">
            @foreach (['All', 'My drafts', 'Published'] as $index => $tab)
                <button type="button" x-on:click="selected = {{ $index }}"
                    :class="selected === {{ $index }} ? @js($segmentActive) : @js($segmentIdle)"
                    class="flex items-center px-3 py-1.5 space-x-2 text-sm rounded-md transition-colors duration-150 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">{{ __($tab) }}</button>
            @endforeach
        </div>
        <div class="flex flex-wrap gap-1 items-center p-1 mt-3 max-w-full rounded-lg bg-gray-950/[0.04] dark:bg-white/5 w-fit">
            <span class="flex items-center px-3 py-1.5 text-sm rounded-md {{ $segmentActive }}">{{ __('Active') }}</span>
            <span class="flex items-center px-3 py-1.5 text-sm rounded-md {{ $segmentIdle }}">{{ __('Idle') }}</span>
            <span class="sg-force-hover"><span class="flex items-center px-3 py-1.5 text-sm rounded-md {{ $segmentIdle }}">{{ __('Hover') }}</span></span>
            <span class="sg-force-focus"><span class="flex items-center px-3 py-1.5 text-sm rounded-md focus-visible:ring-2 focus-visible:ring-primary-500 {{ $segmentIdle }}">{{ __('Focus') }}</span></span>
        </div>
    </div>

    {{-- Icon segmented control (table/switch-view) --}}
    <div class="aura-card" x-data="{ selected: 'list' }">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('View switch (table/switch-view)') }}</span>
        <div class="mt-4">
            <div class="flex aura-control-base">
            <div class="inline-flex isolate gap-0.5 items-stretch p-0.5 h-full rounded-lg bg-gray-950/[0.04] dark:bg-white/5">
                @foreach (['list' => 'List view', 'grid' => 'Grid view', 'kanban' => 'Kanban view'] as $view => $label)
                    <button type="button" x-on:click="selected = '{{ $view }}'"
                        :class="selected === '{{ $view }}' ? @js($segmentActive) : @js($segmentIdle)"
                        class="inline-flex relative justify-center items-center rounded-md transition-colors duration-150 aspect-square focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        <span class="sr-only">{{ __($label) }}</span>
                        <x-aura::icon :icon="$view" size="sm" />
                    </button>
                @endforeach
            </div>
            </div>
        </div>
        <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">{{ __('Same segmented style as the filter tabs, with p-0.5 instead of p-1. The track is one base-height control (36px), the segments are square inside it.') }}</p>
    </div>
</div>

<p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
    {{ __('Form tabs and the resource editor tabs use the same underline style but differ in dark mode: fields/tabs uses dark:text-primary-500, resource-editor uses dark:text-primary-400. Idle tabs get darker on hover in dark mode (dark:hover:text-gray-500).') }}
</p>
