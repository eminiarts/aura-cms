<tr class="bg-white cm-table-row dark:bg-gray-900" wire:key="{{ $row->id }}" data-id="{{ $row->id }}">

    @php
    ray($row)
    @endphp

    @if ($this->settings['selectable'])
        <x-aura::table.cell class="pr-0">
            <x-aura::input.checkbox x-model="selected" :label="$row->id" hideLabel :value="$row->id"
                x-on:click="toggleRow($event, {{ $row->id }})" />
        </x-aura::table.cell>
    @endif

    @foreach ($this->headers as $key => $column)
        @if (optional($this->columns)[$key])
            <td class="px-6 py-4">
                {!! $row->display($key) !!}
            </td>
        @endif
    @endforeach

    @if ($this->settings['actions'])
        <td>
            @include('aura::components.table.row-actions')
        </td>
    @endif
</tr>
