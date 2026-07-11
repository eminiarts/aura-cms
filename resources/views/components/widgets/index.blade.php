<div wire:key="widgets">
    @if(count($widgets) > 0)

        <div class="flex flex-wrap gap-2 justify-end items-center mt-4" wire:key="widgets-filter">
            @if($selected === 'custom')
                <input type="date" id="widgets-date-start" wire:model.live="start"
                    class="py-1.5 pr-2 pl-3 text-sm text-gray-700 bg-white rounded-lg border-0 ring-1 shadow-sm ring-gray-950/10 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10 focus:ring-2 focus:ring-primary-500">
                <span class="text-sm text-gray-400">&ndash;</span>
                <input type="date" id="widgets-date-end" wire:model.live="end"
                    class="py-1.5 pr-2 pl-3 text-sm text-gray-700 bg-white rounded-lg border-0 ring-1 shadow-sm ring-gray-950/10 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10 focus:ring-2 focus:ring-primary-500">
            @endif

            <label for="widgets-date-range" class="sr-only">{{ __('Date range') }}</label>
            <select id="widgets-date-range" wire:model.live="selected"
                class="py-1.5 pr-8 pl-3 text-sm font-medium text-gray-700 bg-white rounded-lg border-0 ring-1 shadow-sm ring-gray-950/10 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10 focus:ring-2 focus:ring-primary-500">
                @foreach(collect($model->widgetSettings['options'])->except('all') as $key => $label)
                    <option value="{{ $key }}">{{ __($label) }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-wrap items-stretch -mx-2 mt-4">
            @foreach ($widgets as $key => $widget)
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
</div>
