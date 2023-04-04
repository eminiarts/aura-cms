<div wire:key="widgets">
    <div wire:key="widgets-filter">
        {{-- Filter here --}}
        {{-- @dump($selected)
        @dump($start)
        @dump($end) --}}
        {{-- @dump($widgets) --}}
        <div class="flex items-center space-x-2">
            <label for="selected" class="block text-sm font-medium text-gray-700">Date Range:</label>
            <select id="selected" wire:model="selected" class="block w-full py-2 pl-3 pr-10 text-base border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @foreach($model->widgetSettings['options'] as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <div>
                @if($selected === 'custom')
                    <label for="start" class="block text-sm font-medium text-gray-700">From:</label>
                    <input type="date" id="start" wire:model="start" class="block w-full py-2 pl-3 pr-10 text-base border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <label for="end" class="block text-sm font-medium text-gray-700">To:</label>
                    <input type="date" id="end" wire:model="end" class="block w-full py-2 pl-3 pr-10 text-base border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @endif
            </div>
        </div>

    </div>

    <div class="flex flex-wrap items-stretch mt-4 -mx-2">
        @foreach ($widgets as $widget)
        {{-- Conditions --}}
        {{-- Widget Style Width --}}


        <div class="px-2" wire-key="widget-{{ $widget['slug'] }}-wrapper" id="widget-{{ $widget['slug'] }}-wrapper">
            {{ $widget['slug'] }}
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
            @livewire(\Livewire\Livewire::getAlias($widget['type']), ['widget' => $widget, 'start' => $start, 'end' => $end, 'model' => $model] )
        </div>
        @endforeach
    </div>
</div>
