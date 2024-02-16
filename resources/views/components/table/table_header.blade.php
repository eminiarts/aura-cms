<thead>
    <tr>
        @if($this->settings['selectable'])
        <x-aura::table.heading class="w-8 pr-0 bg-gray-50 dark:bg-gray-800">
                <x-aura::input.checkbox x-model="selectPage" x-on:click="selectCurrentPage" />
        </x-aura::table.heading>
        @endif

        @if($this->headers)
        @foreach($this->headers as $key => $column)
            @if(optional($this->columns)[$key])
                <x-aura::table.heading sortable wire:click="sortBy('{{ $key }}')" :direction="$sorts[$key] ?? null">{{ __($column) }}</x-aura::table.heading>
            @endif
        @endforeach
        @endif

        @if($this->settings['actions'])
        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
            {{ __('Actions') }}
        </th>
        @endif
    </tr>
</thead>
