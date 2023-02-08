<div class="{{ $width }} px-2">
    <dl class="w-full">
      <div class="aura-card">

        <div class="flex items-start justify-between">
          <dt class="text-sm font-medium text-gray-500 truncate">{{ $this->name }}</dt>
        <div>
          <select wire:model="range" id="range" name="range" class="block w-full py-1 mt-1 text-base border-gray-500/30 rounded-md focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
            @foreach($this->ranges() as $key => $range)
            <option value="{{ $key }}">{{ $range }}</option>
            @endforeach
          </select>
        </div>
        </div>

        <dd class="flex items-baseline mt-1 text-3xl font-semibold text-gray-900">
          {{-- @dump($this->value) --}}

          <p>{{ $this->value['current'] }}</p>


          @if($this->value['increase'] > 0)
          <p class="flex items-baseline ml-2 text-sm font-semibold text-green-600">
          <!-- Heroicon name: solid/arrow-sm-up -->
          <svg class="self-center flex-shrink-0 w-5 h-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
          </svg>
          {{ $this->value['increase'] }}%
        </p>
        @elseif($this->value['increase'] < 0)
         <p class="flex items-baseline ml-2 text-sm font-semibold text-red-600">
          <!-- Heroicon name: solid/arrow-sm-up -->
          <svg class="self-center flex-shrink-0 w-5 h-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
          {{ $this->value['increase'] }}%
        </p>
        @endif
        </dd>

      </div>
    </dl>
</div>
