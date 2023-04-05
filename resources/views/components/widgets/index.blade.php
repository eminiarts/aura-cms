<div wire:key="widgets">
    @if(count($widgets) > 0)
    <div wire:key="widgets-filter">
        {{-- Filter here --}}
        {{-- @dump($selected)
        @dump($start)
        @dump($end) --}}
        {{-- @dump($widgets) --}}

        <div id="widget-filter-dropdown" class="relative flex justify-end">
            <x-aura::dropdown align="right" width="60">
                <x-slot name="trigger">
                    <x-aura::button.border>
                        <x-slot:icon>
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3.38589 5.66687C2.62955 4.82155 2.25138 4.39889 2.23712 4.03968C2.22473 3.72764 2.35882 3.42772 2.59963 3.22889C2.87684 3 3.44399 3 4.57828 3H19.4212C20.5555 3 21.1227 3 21.3999 3.22889C21.6407 3.42772 21.7748 3.72764 21.7624 4.03968C21.7481 4.39889 21.3699 4.82155 20.6136 5.66687L14.9074 12.0444C14.7566 12.2129 14.6812 12.2972 14.6275 12.3931C14.5798 12.4781 14.5448 12.5697 14.5236 12.6648C14.4997 12.7721 14.4997 12.8852 14.4997 13.1113V18.4584C14.4997 18.6539 14.4997 18.7517 14.4682 18.8363C14.4403 18.911 14.395 18.9779 14.336 19.0315C14.2692 19.0922 14.1784 19.1285 13.9969 19.2012L10.5969 20.5612C10.2293 20.7082 10.0455 20.7817 9.89802 20.751C9.76901 20.7242 9.6558 20.6476 9.583 20.5377C9.49975 20.4122 9.49975 20.2142 9.49975 19.8184V13.1113C9.49975 12.8852 9.49975 12.7721 9.47587 12.6648C9.45469 12.5697 9.41971 12.4781 9.37204 12.3931C9.31828 12.2972 9.2429 12.2129 9.09213 12.0444L3.38589 5.66687Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </x-slot>
                        30 Days
                    </x-aura::button.border>
                </x-slot>

                <x-slot name="content">
                    <div class="w-60">
                        <div class="p-4" role="none">
                            {{-- here --}}
                        </div>
                    </div>
                </x-slot>
            </x-aura::dropdown>
        </div>
    </div>

    <div>
        <div class="flex flex-col space-y-2">
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

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

  <script>
      // after 100ms trigger a window resize event to force the chart to redraw
      setTimeout(function() {
          window.dispatchEvent(new Event('resize'));
      }, 0);
      setTimeout(function() {
          window.dispatchEvent(new Event('resize'));
      }, 100);
  </script>
  @endif
</div>
