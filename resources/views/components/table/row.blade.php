<tr class="transition-colors duration-150 ease-in-out cm-table-row hover:bg-gray-50/80 dark:hover:bg-white/[0.04]"
    wire:key="{{ $row->id }}"
    data-id="{{ $row->id }}"
    :class="{ 'bg-primary-50/60 dark:bg-primary-500/10': selected.includes('{{ $row->id }}') }"
    >

    @if ($this->settings['selectable'])
        <x-aura::table.cell class="relative pr-0">
            <span x-show="selected.includes('{{ $row->id }}')" x-cloak
                  class="absolute inset-y-0 left-0 w-0.5 bg-primary-600" aria-hidden="true"></span>
            <x-aura::input.checkbox
    id="checkbox_{{ $row->id }}"
    x-bind:checked="selected.includes({{ $row->id }})"
    hideLabel
    :label="$row->id"
    :value="$row->id"
    x-on:click.stop.prevent="toggleRow($event, {{ $row->id }})"
/>
        </x-aura::table.cell>
    @endif

    @if($this->headers)
    @php $firstVisibleColumn = true; @endphp
    @foreach ($this->headers as $key => $column)
        @if (optional($this->columns)[$key])
            @php
                $display = $row->display($key);
                // Empty cells show the same dash as empty view values.
                $displayIsEmpty = $display === null || $display === '' || (is_string($display) && trim($display) === '');
                $columnType = $columnTypes[$key] ?? null;
                $cellClass = match ($columnType) {
                    'Aura\\Base\\Fields\\Number' => 'text-right whitespace-nowrap',
                    'Aura\\Base\\Fields\\Phone', 'Aura\\Base\\Fields\\Date', 'Aura\\Base\\Fields\\Datetime' => 'whitespace-nowrap',
                    default => '',
                };
            @endphp
            <td class="px-4 py-3 text-sm {{ $cellClass }} {{ $firstVisibleColumn ? 'font-medium text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300' }}">
                @if ($displayIsEmpty)
                    <span class="text-gray-400 dark:text-gray-500">–</span>
                @elseif ($columnType === 'Aura\\Base\\Fields\\Email' && is_string($display))
                    {{-- Long addresses stay on one line; the full address is in the tooltip. --}}
                    <span class="block max-w-[16rem] truncate" title="{{ html_entity_decode(strip_tags($display)) }}">{!! $display !!}</span>
                @else
                    {!! $display !!}
                @endif
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
