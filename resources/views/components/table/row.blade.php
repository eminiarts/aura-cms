<tr class="transition-colors duration-150 ease-in-out cm-table-row hover:bg-gray-50/80 dark:hover:bg-white/[0.04]"
    wire:key="{{ $row->id }}"
    data-id="{{ $row->id }}"
    @if($this->tableRowsAreOrderable()) wire:sort:item="{{ $row->getKey() }}" @endif
    :class="{ 'bg-primary-50/60 dark:bg-primary-500/10': isRowSelected(@js($row->getKey())) }"
    >

    @if ($this->tableRowsAreOrderable())
        <td class="w-8 px-2 py-3 text-aura-muted">
            <button type="button" wire:sort:handle class="inline-flex cursor-grab items-center rounded p-1 hover:text-aura-text focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500" aria-label="{{ __('Reorder row') }}">
                <span aria-hidden="true">⋮⋮</span>
            </button>
        </td>
    @endif

    @if ($this->settings['selectable'])
        <x-aura::table.cell class="relative pr-0">
            <span x-show="isRowSelected(@js($row->getKey()))" x-cloak
                  class="absolute inset-y-0 left-0 w-0.5 bg-primary-600" aria-hidden="true"></span>
            <x-aura::input.checkbox
    id="checkbox_{{ $row->id }}"
    x-bind:checked="isRowSelected({{ \Illuminate\Support\Js::from($row->getKey()) }})"
    hideLabel
    :label="$row->id"
    :value="$row->id"
    x-on:click.stop.prevent="toggleRow($event, {{ \Illuminate\Support\Js::from($row->getKey()) }})"
/>
        </x-aura::table.cell>
    @endif

    @if($this->headers)
    @php $firstVisibleColumn = true; @endphp
    @foreach ($this->headers as $key => $column)
        @if (optional($this->columns)[$key])
            <td class="px-6 py-3 text-sm {{ $firstVisibleColumn ? 'font-medium text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300' }}">
                {!! $row->display($key) !!}
            </td>
            @php $firstVisibleColumn = false; @endphp
        @endif
    @endforeach
    @endif

    @if ($this->settings['actions'])
        <td class="px-3 py-2">
            @include('aura::components.table.row-actions')
        </td>
    @endif
</tr>
