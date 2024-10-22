<tr class="bg-white transition-colors duration-200 ease-in-out cm-table-row dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800"
    wire:key="{{ $row->id }}"
    data-id="{{ $row->id }}"
    :class="{ 'bg-primary-50 dark:bg-primary-900/50': selected.includes('{{ $row->id }}') }"
    >

    @if ($this->settings['selectable'])
        <x-aura::table.cell class="pr-0">
            <x-aura::input.checkbox
                id="checkbox_{{ $row->id }}"
                x-bind:checked="selected.includes('{{ $row->id }}')"
                hideLabel
                :label="$row->id"
                :value="$row->id"
                x-on:click.stop="toggleRow($event, {{ $row->id }})"
            />
        </x-aura::table.cell>
    @endif

    @if($this->headers)
    @foreach ($this->headers as $key => $column)
        @if (optional($this->columns)[$key])
            <td class="px-6 py-4">
                {!! $row->display($key) !!}
            </td>
        @endif
    @endforeach
    @endif

    @if ($this->settings['actions'])
        <td>
            @include('aura::components.table.row-actions')
        </td>
    @endif
</tr>
