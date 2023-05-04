<tr class="bg-white cm-table-row dark:bg-gray-900" wire:key="{{ $row->id }}" data-id="{{ $row->id }}">
    <x-aura::table.cell class="pr-0">
        <x-aura::input.checkbox x-model="selected" :value="$row->id" x-on:click="toggleRow($event, {{ $row->id }})" />
    </x-aura::table.cell>

    @foreach($this->headers as $key => $column)
        @if(optional($this->columns)[$key])
            <td class="px-6 py-4">
                {!! $row->display($key) !!}
            </td>
        @endif
    @endforeach

    <td>
        @include('aura::components.table.row-actions')
    </td>
</tr>
