{{-- An empty list (list-view's $isEmpty) keeps its header without the bulk checkbox and sorting. --}}
@php($headerIsEmpty = $isEmpty ?? false)
<thead>
    <tr class="border-b border-aura-hairline">
        @if($this->settings['selectable'] && ! $headerIsEmpty)
        <x-aura::table.heading class="pr-0 w-8">
                <x-aura::input.checkbox x-model="selectPage" x-on:click="selectCurrentPage" />
        </x-aura::table.heading>
        @endif

        @if($this->headers)
        @foreach($this->headers as $key => $column)
            @if(optional($this->columns)[$key])
                <x-aura::table.heading :sortable="! $headerIsEmpty" wire:click="sortBy('{{ $key }}')" :direction="$sorts[$key] ?? null" :type="$columnTypes[$key] ?? null" :align="($columnTypes[$key] ?? null) === 'Aura\\Base\\Fields\\Number' ? 'right' : 'left'">{{ __($column) }}</x-aura::table.heading>
            @endif
        @endforeach
        @endif

        @if($this->settings['actions'])
        <th class="table-row-actions px-4 py-2.5 aura-table-header text-right">
            <span class="sr-only">{{ __('Actions') }}</span>
        </th>
        @endif
    </tr>
</thead>
