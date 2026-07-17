<thead>
    <tr class="bg-gray-50/80 dark:bg-white/[0.03] border-b border-gray-200/80 dark:border-white/10">
        @if($this->settings['selectable'])
        <x-aura::table.heading class="pr-0 w-8">
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
        <th class="table-row-actions px-6 py-2.5 text-[11px] font-semibold uppercase tracking-wide text-right text-gray-500 dark:text-gray-400">
            <span class="sr-only">{{ __('Actions') }}</span>
        </th>
        @endif
    </tr>
</thead>
