@php
    $selectOptions = ['draft' => 'Draft', 'published' => 'Published'];
    $heightPill = 'inline-flex self-start px-1.5 py-0.5 text-[11px] font-semibold tabular-nums rounded bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-200 data-[sg-mismatch=true]:bg-red-100 data-[sg-mismatch=true]:text-red-700 dark:data-[sg-mismatch=true]:bg-red-500/20 dark:data-[sg-mismatch=true]:text-red-300';
    $caption = 'max-w-44 text-[11px] leading-4 text-gray-500 dark:text-gray-400';
    $searchInput = 'block h-10 w-full py-2 pl-9 pr-3 text-sm text-gray-900 bg-white rounded-lg border-0 ring-1 shadow-xs transition appearance-none placeholder:text-gray-400 dark:placeholder:text-gray-500 ring-gray-950/10 dark:bg-gray-800 dark:text-gray-100 dark:ring-white/10 focus:outline-none focus:ring-2 focus:ring-primary-500 z-[1]';
@endphp

<div class="mt-6 space-y-4">
    {{-- Base --}}
    <div class="aura-card" x-data="auraMeasure" data-sizing-row="base">
        <div class="flex flex-wrap gap-x-3 items-baseline">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Base') }}</span>
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('Reference: button base. Red = differs from the reference.') }}</span>
        </div>
        <div class="flex flex-wrap gap-x-5 gap-y-6 items-start mt-4">
            <div class="flex flex-col gap-2 items-start" data-sg-measure data-sg-reference>
                <x-aura::button>{{ __('Save') }}</x-aura::button>
                <span class="{{ $heightPill }} ring-1 ring-primary-500" data-sg-height>…</span>
                <code class="{{ $caption }}">button · base</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                <x-aura::button.border>{{ __('Cancel') }}</x-aura::button.border>
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">button.border · base</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                <x-aura::button>
                    <x-slot:icon><x-aura::icon icon="plus" size="sm" /></x-slot:icon>
                    {{ __('Create') }}
                </x-aura::button>
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">button · icon slot</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                <x-aura::button.border><x-aura::icon icon="dots" size="xs" /></x-aura::button.border>
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">button.border · icon only</code>
            </div>
            <div class="flex flex-col gap-2 items-start w-44" data-sg-measure>
                <x-aura::input.text placeholder="{{ __('Title') }}" />
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">input.text</code>
            </div>
            <div class="flex flex-col gap-2 items-start w-44" data-sg-measure>
                <x-aura::input.text prefix="https://" placeholder="aura.test" />
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">input.text · prefix</code>
            </div>
            <div class="flex flex-col gap-2 items-start w-40" data-sg-measure>
                <x-aura::input.select name="styleguide-sizing-select" :options="$selectOptions" selected="draft" />
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">input.select</code>
            </div>
            <div class="flex flex-col gap-2 items-start w-44" data-sg-measure>
                <x-aura::input.text type="date" />
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">input.text · type=date</code>
            </div>
        </div>
    </div>

    {{-- Small --}}
    <div class="aura-card" x-data="auraMeasure" data-sizing-row="small">
        <div class="flex flex-wrap gap-x-3 items-baseline">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Small') }}</span>
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('Reference: button sm. Inputs have no sm size.') }}</span>
        </div>
        <div class="flex flex-wrap gap-x-5 gap-y-6 items-start mt-4">
            <div class="flex flex-col gap-2 items-start" data-sg-measure data-sg-reference>
                <x-aura::button size="sm">{{ __('Save') }}</x-aura::button>
                <span class="{{ $heightPill }} ring-1 ring-primary-500" data-sg-height>…</span>
                <code class="{{ $caption }}">button · sm</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                <x-aura::button.border size="sm">{{ __('Cancel') }}</x-aura::button.border>
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">button.border · sm</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                <select class="py-1.5 pr-8 pl-3 text-sm font-medium text-gray-700 bg-white rounded-lg border-0 ring-1 shadow-sm ring-gray-950/10 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10 focus:ring-2 focus:ring-primary-500">
                    <option>{{ __('Last 30 days') }}</option>
                </select>
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">{{ __('widgets/index date range select') }}</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                <input type="date" class="py-1.5 pr-2 pl-3 text-sm text-gray-700 bg-white rounded-lg border-0 ring-1 shadow-sm ring-gray-950/10 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10 focus:ring-2 focus:ring-primary-500 [color-scheme:light] dark:[color-scheme:dark]">
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">{{ __('widgets/index custom date input') }}</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                <span class="inline-flex justify-center items-center px-2 h-8 text-sm font-medium rounded-lg min-w-8 bg-primary-50 text-primary-700 ring-1 ring-primary-200 dark:bg-primary-500/15 dark:text-primary-300 dark:ring-primary-500/20" data-sg-target>1</span>
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">{{ __('table/pagination page (h-8)') }}</code>
            </div>
        </div>
    </div>

    {{-- Extra small --}}
    <div class="aura-card" x-data="auraMeasure" data-sizing-row="xs">
        <div class="flex flex-wrap gap-x-3 items-baseline">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Extra small') }}</span>
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('Reference: button xs.') }}</span>
        </div>
        <div class="flex flex-wrap gap-x-5 gap-y-6 items-start mt-4">
            <div class="flex flex-col gap-2 items-start" data-sg-measure data-sg-reference>
                <x-aura::button size="xs">{{ __('Save') }}</x-aura::button>
                <span class="{{ $heightPill }} ring-1 ring-primary-500" data-sg-height>…</span>
                <code class="{{ $caption }}">button · xs</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                <x-aura::button.border size="xs">{{ __('Cancel') }}</x-aura::button.border>
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">button.border · xs</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                <x-aura::button.border size="icon-xs"><x-aura::icon icon="dots" size="xs" /></x-aura::button.border>
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">button.border · icon-xs</code>
            </div>
            <div class="flex flex-col gap-2 items-start w-40" data-sg-measure>
                <x-aura::input.text size="xs" placeholder="{{ __('Title') }}" />
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">input.text · xs</code>
            </div>
            <div class="flex flex-col gap-2 items-start w-36" data-sg-measure>
                <x-aura::input.select name="styleguide-sizing-select-xs" :options="$selectOptions" selected="draft" size="xs" />
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">input.select · xs</code>
            </div>
        </div>
    </div>

    {{-- Table toolbar --}}
    <div class="aura-card" x-data="auraMeasure" data-sizing-row="toolbar">
        <div class="flex flex-wrap gap-x-3 items-baseline">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Table toolbar') }}</span>
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('Reference: the search input (h-10) in livewire/table. These controls sit in one row.') }}</span>
        </div>
        <div class="flex flex-wrap gap-x-5 gap-y-6 items-start mt-4" x-data="{ toggleFilters() {} }">
            <div class="flex flex-col gap-2 items-start w-56" data-sg-measure data-sg-reference>
                <div class="relative">
                    <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                    </div>
                    <input type="text" class="{{ $searchInput }}" placeholder="{{ __('Search for items') }}">
                </div>
                <span class="{{ $heightPill }} ring-1 ring-primary-500" data-sg-height>…</span>
                <code class="{{ $caption }}">{{ __('search input · h-10') }}</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                <div class="inline-flex isolate gap-0.5 items-center p-0.5 rounded-lg bg-gray-950/[0.04] dark:bg-white/5" data-sg-target>
                    <button type="button" class="inline-flex relative items-center p-1.5 text-gray-900 bg-white rounded-md ring-1 shadow-xs ring-gray-950/[0.07] dark:bg-gray-700 dark:text-white dark:ring-white/10"><x-aura::icon icon="list" size="sm" /></button>
                    <button type="button" class="inline-flex relative items-center p-1.5 text-gray-500 rounded-md dark:text-gray-400"><x-aura::icon icon="grid" size="sm" /></button>
                </div>
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">table/switch-view</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                <x-aura::button.border class="h-10">
                    <x-slot:icon><x-aura::icon icon="table" size="sm" /></x-slot:icon>
                    {{ __('Settings') }}
                </x-aura::button.border>
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">table/settings · h-10</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                @include('aura::components.table.filter')
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">table/filter · sm + h-10</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                <button type="button" class="inline-flex gap-1.5 items-center px-3 py-1.5 h-9 text-sm font-medium text-gray-700 bg-white rounded-lg ring-1 shadow-xs transition-colors duration-150 ring-gray-950/10 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10 dark:hover:bg-gray-700/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    {{ __('Actions') }}
                    <svg class="-mr-0.5 w-4 h-4 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </button>
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">table/bulk-actions · h-9</code>
            </div>
        </div>
    </div>

    <div class="p-4 text-sm text-gray-600 bg-gray-50 rounded-lg ring-1 ring-gray-950/5 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10" data-sizing-notes>
        <span class="font-semibold text-gray-900 dark:text-white">{{ __('How controls size themselves') }}</span>
        <ul class="mt-2 space-y-1 list-disc list-inside">
            <li>{{ __('Buttons: padding + line height per size (xs py-1.5 text-xs = 28px, sm py-1.5 leading-5 = 32px, base py-2 text-sm = 36px, lg py-2.5 text-base = 44px, xl py-3 text-lg = 52px).') }}</li>
            <li>{{ __('Inputs and selects: py-2 text-sm = 36px (base) or py-1 text-xs = 24px (xs). There is no sm size.') }}</li>
            <li>{{ __('Known mismatches: an icon-only base button is 32px (16px icon instead of a 20px line). xs inputs and selects are 24px next to 28px xs buttons. The table toolbar mixes 40px (search, settings, filters) with 36px (view switch, bulk actions).') }}</li>
            <li>{{ __('Heights were measured in Chromium with the default font stack; another font can shift them by a pixel.') }}</li>
        </ul>
    </div>
</div>
