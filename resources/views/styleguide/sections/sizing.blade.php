@php
    $selectOptions = ['draft' => 'Draft', 'published' => 'Published'];
    $heightPill = 'inline-flex self-start px-1.5 py-0.5 text-[11px] font-semibold tabular-nums rounded bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-200 data-[sg-mismatch=true]:bg-red-100 data-[sg-mismatch=true]:text-red-700 dark:data-[sg-mismatch=true]:bg-red-500/20 dark:data-[sg-mismatch=true]:text-red-300';
    $caption = 'max-w-44 text-[11px] leading-4 text-gray-500 dark:text-gray-400';
    // The same markup as the search input in livewire/table.
    $searchInput = 'aura-field aura-control-base block w-full pl-9 pr-3 text-sm text-gray-900 bg-white border-0 ring-1 shadow-xs transition appearance-none placeholder:text-gray-400 dark:placeholder:text-gray-500 ring-gray-950/10 dark:bg-gray-800 dark:text-gray-100 dark:ring-white/10 focus:outline-none focus:ring-2 focus:ring-primary-500 z-[1]';
    // The same markup as the date range select and dates in widgets/index.
    $widgetSelect = 'aura-field aura-control-sm pr-8 pl-3 text-sm font-medium text-gray-700 bg-white border-0 ring-1 shadow-sm ring-gray-950/10 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10 focus:ring-2 focus:ring-primary-500';
    $widgetDate = 'aura-field aura-control-sm pr-2 pl-3 text-sm text-gray-700 bg-white border-0 ring-1 shadow-sm ring-gray-950/10 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10 focus:ring-2 focus:ring-primary-500 [color-scheme:light] dark:[color-scheme:dark]';

    $scale = [
        'xs' => ['label' => __('Extra small'), 'variable' => '--aura-control-height-xs', 'usage' => __('Table filters, row actions')],
        'sm' => ['label' => __('Small'), 'variable' => '--aura-control-height-sm', 'usage' => __('Widget filters, pagination')],
        'base' => ['label' => __('Base'), 'variable' => '--aura-control-height', 'usage' => __('Forms, dialogs, page actions, the table toolbar')],
        'lg' => ['label' => __('Large'), 'variable' => '--aura-control-height-lg', 'usage' => __('Login, empty states')],
    ];
@endphp

<div class="mt-6 space-y-4">
    @foreach ($scale as $size => $row)
        <div class="aura-card" x-data="auraMeasure" data-sizing-row="{{ $size }}">
            <div class="flex flex-wrap gap-x-3 items-baseline">
                <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ $row['label'] }}</span>
                <code class="text-xs text-gray-500 dark:text-gray-400">{{ $row['variable'] }}</code>
                <span class="text-xs text-gray-400 dark:text-gray-500" x-data="auraCssVar(@js($row['variable']))" x-text="value"></span>
                <span class="text-xs text-gray-400 dark:text-gray-500">· {{ $row['usage'] }} · {{ __('Reference: button :size. Red = differs from the reference.', ['size' => $size]) }}</span>
            </div>
            <div class="flex flex-wrap gap-x-5 gap-y-6 items-start mt-4">
                <div class="flex flex-col gap-2 items-start" data-sg-measure data-sg-reference>
                    <x-aura::button :size="$size">{{ __('Save') }}</x-aura::button>
                    <span class="{{ $heightPill }} ring-1 ring-primary-500" data-sg-height>…</span>
                    <code class="{{ $caption }}">button · {{ $size }}</code>
                </div>
                <div class="flex flex-col gap-2 items-start" data-sg-measure>
                    <x-aura::button.border :size="$size">
                        <x-slot:icon><x-aura::icon icon="plus" size="sm" /></x-slot:icon>
                        {{ __('Create') }}
                    </x-aura::button.border>
                    <span class="{{ $heightPill }}" data-sg-height>…</span>
                    <code class="{{ $caption }}">button.border · {{ __('icon slot') }}</code>
                </div>
                <div class="flex flex-col gap-2 items-start" data-sg-measure>
                    <x-aura::button.border :size="$size === 'xs' ? 'icon-xs' : $size"><x-aura::icon icon="dots" size="xs" /></x-aura::button.border>
                    <span class="{{ $heightPill }}" data-sg-height>…</span>
                    <code class="{{ $caption }}">{{ $size === 'xs' ? 'button.border · icon-xs' : __('button.border · icon only (square)') }}</code>
                </div>
                <div class="flex flex-col gap-2 items-start w-44" data-sg-measure>
                    <x-aura::input.text :size="$size" placeholder="{{ __('Title') }}" />
                    <span class="{{ $heightPill }}" data-sg-height>…</span>
                    <code class="{{ $caption }}">input.text · {{ $size }}</code>
                </div>
                <div class="flex flex-col gap-2 items-start w-44" data-sg-measure>
                    <x-aura::input.text :size="$size" prefix="https://" placeholder="aura.test" />
                    <span class="{{ $heightPill }}" data-sg-height>…</span>
                    <code class="{{ $caption }}">input.text · prefix</code>
                </div>
                <div class="flex flex-col gap-2 items-start w-40" data-sg-measure>
                    <x-aura::input.select :name="'styleguide-sizing-select-'.$size" :options="$selectOptions" selected="draft" :size="$size" />
                    <span class="{{ $heightPill }}" data-sg-height>…</span>
                    <code class="{{ $caption }}">input.select · {{ $size }}</code>
                </div>
                @if ($size === 'base')
                    <div class="flex flex-col gap-2 items-start w-44" data-sg-measure>
                        <x-aura::input.text type="date" />
                        <span class="{{ $heightPill }}" data-sg-height>…</span>
                        <code class="{{ $caption }}">input.text · type=date</code>
                    </div>
                @endif
                @if ($size === 'sm')
                    <div class="flex flex-col gap-2 items-start" data-sg-measure>
                        <select class="{{ $widgetSelect }}">
                            <option>{{ __('Last 30 days') }}</option>
                        </select>
                        <span class="{{ $heightPill }}" data-sg-height>…</span>
                        <code class="{{ $caption }}">{{ __('widgets/index date range select') }}</code>
                    </div>
                    <div class="flex flex-col gap-2 items-start" data-sg-measure>
                        <input type="date" class="{{ $widgetDate }}">
                        <span class="{{ $heightPill }}" data-sg-height>…</span>
                        <code class="{{ $caption }}">{{ __('widgets/index custom date input') }}</code>
                    </div>
                    <div class="flex flex-col gap-2 items-start" data-sg-measure>
                        <span class="inline-flex justify-center items-center px-2 text-sm font-medium aura-button aura-control-sm min-w-8 bg-primary-50 text-primary-700 ring-1 ring-primary-200 dark:bg-primary-500/15 dark:text-primary-300 dark:ring-primary-500/20" data-sg-target>1</span>
                        <span class="{{ $heightPill }}" data-sg-height>…</span>
                        <code class="{{ $caption }}">{{ __('table/pagination page') }}</code>
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    {{-- Table toolbar --}}
    <div class="aura-card" x-data="auraMeasure" data-sizing-row="toolbar">
        <div class="flex flex-wrap gap-x-3 items-baseline">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Table toolbar') }}</span>
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('Reference: the search input in livewire/table. These controls sit in one row, all at base.') }}</span>
        </div>
        <div class="flex flex-wrap gap-x-5 gap-y-6 items-start mt-4" x-data="{ toggleFilters() {} }">
            <div class="flex flex-col gap-2 items-start w-56" data-sg-measure data-sg-reference>
                <div class="relative w-full">
                    <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                    </div>
                    <input type="text" class="{{ $searchInput }}" placeholder="{{ __('Search for items') }}">
                </div>
                <span class="{{ $heightPill }} ring-1 ring-primary-500" data-sg-height>…</span>
                <code class="{{ $caption }}">{{ __('search input') }}</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                <div class="flex aura-control-base" data-sg-target>
                    <div class="inline-flex isolate gap-0.5 items-stretch p-0.5 h-full rounded-lg bg-gray-950/[0.04] dark:bg-white/5">
                        <button type="button" class="inline-flex relative justify-center items-center text-gray-900 bg-white rounded-md ring-1 shadow-xs aspect-square ring-gray-950/[0.07] dark:bg-gray-700 dark:text-white dark:ring-white/10"><span class="sr-only">{{ __('List view') }}</span><x-aura::icon icon="list" size="sm" /></button>
                        <button type="button" class="inline-flex relative justify-center items-center text-gray-500 rounded-md aspect-square dark:text-gray-400"><span class="sr-only">{{ __('Grid view') }}</span><x-aura::icon icon="grid" size="sm" /></button>
                    </div>
                </div>
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">{{ __('table/switch-view (track)') }}</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                <x-aura::button.border>
                    <x-slot:icon><x-aura::icon icon="table" size="sm" /></x-slot:icon>
                    {{ __('Settings') }}
                </x-aura::button.border>
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">table/settings</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                @include('aura::components.table.filter')
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">table/filter</code>
            </div>
            <div class="flex flex-col gap-2 items-start" data-sg-measure>
                <button type="button" class="inline-flex gap-1.5 items-center px-3 text-sm font-medium text-gray-700 bg-white ring-1 shadow-xs transition-colors duration-150 aura-button aura-control-base ring-gray-950/10 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10 dark:hover:bg-gray-700/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    {{ __('Actions') }}
                    <svg class="-mr-0.5 w-4 h-4 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </button>
                <span class="{{ $heightPill }}" data-sg-height>…</span>
                <code class="{{ $caption }}">table/bulk-actions</code>
            </div>
        </div>
    </div>

    <div class="p-4 text-sm text-gray-600 bg-gray-50 rounded-lg ring-1 ring-gray-950/5 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10" data-sizing-notes>
        <span class="font-semibold text-gray-900 dark:text-white">{{ __('How controls size themselves') }}</span>
        <ul class="mt-2 space-y-1 list-disc list-inside">
            <li>{{ __('One height per size from --aura-control-height-*: xs 28px, sm 32px, base 36px, lg 44px (xl 52px, buttons only). The .aura-control-{size} rule sets the height with no vertical padding; buttons centre their content with flexbox and inputs and selects centre their text, so the font size never changes the height.') }}</li>
            <li>{{ __('Buttons, inputs and selects take the same size prop. Text is 12px at xs, 14px at sm and base, 16px at lg.') }}</li>
            <li>{{ __('Icon-only buttons are square at the control height (.aura-control-square); icon-xs is the square xs button.') }}</li>
            <li>{{ __('Sizes are fixed: density changes padding and gaps, not control heights. Expert mode can override the base height (expert-control-height).') }}</li>
            <li>{{ __('Fields that grow with their content (tags, multi-select) use the height as a minimum (.aura-control-min-*). Every field, addon and button uses --aura-control-radius.') }}</li>
            <li>{{ __('Page actions (Create, Save, Edit) are base. The table toolbar is base; the view switch is one base-height track with 32px segments inside.') }}</li>
        </ul>
    </div>
</div>
