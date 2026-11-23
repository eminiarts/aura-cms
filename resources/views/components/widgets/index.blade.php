<div wire:key="widgets">
    @if(count($widgets) > 0)

        <div class="flex flex-wrap gap-2 justify-end items-center mt-4" wire:key="widgets-filter">
            @if($selected === 'custom')
                <input type="date" id="widgets-date-start" wire:model.live="start"
                    class="aura-field aura-control-sm pr-2 pl-3 text-sm">
                <span class="text-sm text-gray-400">&ndash;</span>
                <input type="date" id="widgets-date-end" wire:model.live="end"
                    class="aura-field aura-control-sm pr-2 pl-3 text-sm">
            @endif

            <label for="widgets-date-range" class="sr-only">{{ __('Date range') }}</label>
            <select id="widgets-date-range" wire:model.live="selected"
                class="aura-field aura-control-sm pr-8 pl-3 text-sm font-medium">
                @foreach(collect($model->widgetSettings['options'])->except('all') as $key => $label)
                    <option value="{{ $key }}">{{ __($label) }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-wrap items-stretch mt-4 aura-layout-row aura-widget-grid">
            @foreach ($widgets as $key => $widget)
                <div class="aura-layout-cell" wire:key="widget-{{ $widget['slug'] }}-wrapper" id="widget-{{ $widget['slug'] }}-wrapper">
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
