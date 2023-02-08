<thead>
    <tr>
        <x-table.heading class="w-8 pr-0 bg-gray-50 dark:bg-gray-800">
            <x-input.checkbox wire:model="selectPage" />
        </x-table.heading>

        @foreach($this->headers as $key => $column)
            @if(optional($this->columns)[$key])
                {{-- multi-column sorting? --}}
                <x-table.heading sortable wire:click="sortBy('{{ $key }}')" :direction="$sorts[$key] ?? null">{{ $column }}</x-table.heading>
                {{-- <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 bg-gray-50 dark:bg-gray-800 dark:text-gray-400 whitespace-nowrap">{{ $column }}</th> --}}
            @endif
        @endforeach

        <th class="px-6 py-3 text-xs font-semibold text-left text-gray-600 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">Actions</th>
    </tr>
</thead>
