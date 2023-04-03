<div>
    <div>
        {{-- Filter here --}}
        @dump($selected)
        @dump($start)
        @dump($end)
        {{-- @dump($widgets) --}}
        <div class="flex items-center space-x-2">
            <label for="selected" class="block text-sm font-medium text-gray-700">Date Range:</label>
            <select id="selected" wire:model="selected" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                @foreach($model->widgetSettings['options'] as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            @if($selected === 'custom')
            <label for="start" class="block text-sm font-medium text-gray-700">From:</label>
            <input type="date" id="start" wire:model="start" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
            <label for="end" class="block text-sm font-medium text-gray-700">To:</label>
            <input type="date" id="end" wire:model="end" class="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
            @endif
        </div>
        
    </div>
    
    <div class="flex flex-wrap mt-4 -mx-2">
        @foreach ($widgets as $widget)
        {{-- Conditions --}}
        @livewire(\Livewire\Livewire::getAlias(get_class($widget['widget'])), ['widget' => $widget, 'start' => $start, 'end' => $end] )
        @endforeach
    </div>
</div>