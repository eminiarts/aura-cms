<div wire:key="widgets">
    @if(count($widgets) > 0)
        @php
            // Index pages (compact): value and sparkline widgets share one slim strip, hairline-divided
            // cells sized by their style width, with the date range as the strip's last cell. Large
            // charts (bar, donut, pie) keep their card grid below it.
            $fitsStrip = fn ($widget) => $compact && is_string($widget['type'] ?? null)
                && method_exists($widget['type'], 'supportsCompact') && $widget['type']::supportsCompact();
            $stripWidgets = collect($widgets)->filter($fitsStrip);
            $cardWidgets = collect($widgets)->reject($fitsStrip);

            $rangeOptions = collect($model->widgetSettings['options'])->except('all');
            $dateInput = 'py-1.5 pr-2 pl-3 text-sm text-gray-700 bg-white rounded-lg border-0 ring-1 shadow-sm ring-gray-950/10 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10 focus:ring-2 focus:ring-primary-500';
        @endphp

        @if ($stripWidgets->isNotEmpty())
            @php
                $rangeSelect = 'py-1 pr-8 pl-2 text-sm font-medium text-gray-600 bg-transparent rounded-md border-0 transition-colors duration-150 cursor-pointer hover:text-gray-900 hover:bg-gray-950/[0.04] dark:text-gray-300 dark:hover:text-white dark:hover:bg-white/5 focus:ring-2 focus:ring-primary-500';
            @endphp
            {{-- Phones: the date range takes its own row above the strip (a copy of the strip's last cell, which
                 phones hide), so the value cells share one row (charts hidden). --}}
            <div class="flex flex-wrap gap-2 items-center -ml-2 mt-3 sm:hidden" wire:key="widgets-filter-mobile">
                <label for="widgets-date-range-mobile" class="sr-only">{{ __('Date range') }}</label>
                <select id="widgets-date-range-mobile" wire:model.live="selected" class="{{ $rangeSelect }}">
                    @foreach($rangeOptions as $key => $label)
                        <option value="{{ $key }}">{{ __($label) }}</option>
                    @endforeach
                </select>

                @if($selected === 'custom')
                    <input type="date" id="widgets-date-start-mobile" wire:model.live="start" aria-label="{{ __('Start date') }}" class="{{ $dateInput }}">
                    <span class="text-sm text-gray-400">&ndash;</span>
                    <input type="date" id="widgets-date-end-mobile" wire:model.live="end" aria-label="{{ __('End date') }}" class="{{ $dateInput }}">
                @endif
            </div>

            <div class="flex overflow-hidden flex-wrap gap-px mt-2 mb-5 sm:mt-4 sm:mb-0 rounded-panel ring-1 bg-gray-950/[0.06] ring-aura-edge dark:bg-white/10" wire:key="widgets-strip" data-widgets-strip>
                @foreach ($stripWidgets as $key => $widget)
                    {{-- Cells grow by their style width from a 9rem basis (phones 5.5rem, three to a row). --}}
                    <div class="min-w-0 bg-white dark:bg-gray-800 shrink basis-[5.5rem] sm:basis-36" style="flex-grow: {{ optional(optional($widget)['style'])['width'] ?? '100' }}"
                        wire:key="widget-{{ $widget['slug'] }}-wrapper" id="widget-{{ $widget['slug'] }}-wrapper">
                        @livewire($widget['type'], ['widget' => $widget, 'model' => $model, 'start' => $start, 'end' => $end, 'compact' => true], key($widget['slug']))
                    </div>
                @endforeach

                <div class="hidden flex-wrap grow gap-2 justify-end items-center px-3 py-2 bg-white sm:flex dark:bg-gray-800" wire:key="widgets-filter">
                    @if($selected === 'custom')
                        <input type="date" id="widgets-date-start" wire:model.live="start" aria-label="{{ __('Start date') }}" class="{{ $dateInput }}">
                        <span class="text-sm text-gray-400">&ndash;</span>
                        <input type="date" id="widgets-date-end" wire:model.live="end" aria-label="{{ __('End date') }}" class="{{ $dateInput }}">
                    @endif

                    <label for="widgets-date-range" class="sr-only">{{ __('Date range') }}</label>
                    <select id="widgets-date-range" wire:model.live="selected" class="{{ $rangeSelect }}">
                        @foreach($rangeOptions as $key => $label)
                            <option value="{{ $key }}">{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @else
            <div class="flex flex-wrap gap-2 justify-end items-center mt-4" wire:key="widgets-filter">
                @if($selected === 'custom')
                    <input type="date" id="widgets-date-start" wire:model.live="start" class="{{ $dateInput }}">
                    <span class="text-sm text-gray-400">&ndash;</span>
                    <input type="date" id="widgets-date-end" wire:model.live="end" class="{{ $dateInput }}">
                @endif

                <label for="widgets-date-range" class="sr-only">{{ __('Date range') }}</label>
                <select id="widgets-date-range" wire:model.live="selected"
                    class="py-1.5 pr-8 pl-3 text-sm font-medium text-gray-700 bg-white rounded-lg border-0 ring-1 shadow-sm ring-gray-950/10 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10 focus:ring-2 focus:ring-primary-500">
                    @foreach($rangeOptions as $key => $label)
                        <option value="{{ $key }}">{{ __($label) }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        @if ($cardWidgets->isNotEmpty())
        <div class="flex flex-wrap items-stretch -mx-2 mt-4">
            @foreach ($cardWidgets as $key => $widget)
                <div class="px-2 pb-4" wire:key="widget-{{ $widget['slug'] }}-wrapper" id="widget-{{ $widget['slug'] }}-wrapper">
                    <style>
                        #widget-{{ $widget['slug'] }}-wrapper {
                            width: {{ optional(optional($widget)['style'])['width'] ?? '100' }}%;
                        }

                        @media screen and (max-width: 768px) {
                            #widget-{{ $widget['slug'] }}-wrapper {
                                width: 100%;
                            }
                        }
                    </style>
                    <div class="h-full" wire:key="widgets_component_{{ $key }}">
                        @livewire($widget['type'], ['widget' => $widget, 'model' => $model, 'start' => $start, 'end' => $end], key($widget['slug']))
                    </div>
                </div>
            @endforeach
        </div>
        @endif

    @endif
</div>
