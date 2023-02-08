<thead>
    <tr>
        <x-aura::table.heading class="w-8 pr-0 bg-gray-50 dark:bg-gray-800">
            <x-aura::input.checkbox wire:model="selectPage" />
        </x-aura::table.heading>

        @foreach($this->headers as $key => $column)
            @if(optional($this->columns)[$key])
                {{-- multi-column sorting? --}}
                <x-aura::table.heading sortable wire:click="sortBy('{{ $key }}')" :direction="$sorts[$key] ?? null">{{ $column }}</x-aura::table.heading>
                {{-- <th class="px-aura::6 py-3 text-xs font-semibold text-left text-gray-600 bg-gray-50 dark:bg-gray-800 dark:text-gray-400 whitespace-nowrap">{{ $column }}</th> --}}
            @endif
        @endforeach

        <th class="px-aura::6 py-3 text-xs font-semibold text-left text-gray-600 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">Actions</th>
    </tr>
</thead>
