@php
    $viewInModal = ! empty($this->settings['view_in_modal']);
    $canView = auth()->user()?->can('view', $row) ?? false;
    $rowViewUrl = $canView && ! $viewInModal ? $row->viewUrl() : null;

    // Desktop: the whole row opens the record (same target as the eye action), so the actions can
    // stay hidden until hover. Clicks on links, controls and a text selection are left alone;
    // a modifier click opens a new tab. Phones use the stacked list, which is one link per row.
    $rowOpen = null;

    if ($canView && $viewInModal) {
        $rowOpen = "\$dispatch('openModal', { component: 'aura::resource-view-modal', arguments: { 'resource': {$row->id}, 'type': '{$row->getType()}' }, modalAttributes: {'slideOver': true }})";
    } elseif ($rowViewUrl) {
        $rowOpen = '($event.metaKey || $event.ctrlKey) ? window.open('.Js::from($rowViewUrl).", '_blank') : Livewire.navigate(".Js::from($rowViewUrl).')';
    }
@endphp
<tr class="transition-colors duration-150 ease-in-out cm-table-row row-hover:bg-gray-50/80 dark:row-hover:bg-white/[0.04] {{ $rowOpen ? 'sm:cursor-pointer' : '' }}"
    wire:key="{{ $row->id }}"
    data-id="{{ $row->id }}"
    :class="{ 'bg-primary-50/60 dark:bg-primary-500/10': selected.includes('{{ $row->id }}') }"
    @if ($rowOpen)
    x-on:click="if (window.matchMedia('(min-width: 640px)').matches && ! $event.target.closest('a, button, input, label, select, textarea, [data-row-click-ignore]') && ! window.getSelection().toString()) { {{ $rowOpen }} }"
    @endif
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
                @elseif ($firstVisibleColumn)
                    {{-- Leading column is the record: its avatar (when the resource has one), the name, and a link
                         to the record when the viewer may open it. No link around a value that is itself a link. --}}
                    @php
                        $hasAvatar = method_exists($row, 'avatarShape') && $row->avatarShape();
                        $leadUrl = ! (is_string($display) && stripos($display, '<a') !== false) ? $rowViewUrl : null;
                    @endphp
                    <div class="flex gap-2.5 items-center min-w-0">
                        @if ($hasAvatar && ($hasOwnTitle ?? true))
                            <x-aura::avatar :record="$row" size="sm" />
                        @elseif ($hasAvatar)
                            {{-- No title() of its own: the generic "Type (#id)" would give every row the same
                                 initial, so the avatar takes the leading column's text instead. --}}
                            <x-aura::avatar :name="trim(html_entity_decode(strip_tags((string) $display), ENT_QUOTES))" :shape="$row->avatarShape()" :image-id="$row->avatarImageId()" size="sm" />
                        @endif
                        @if ($leadUrl)
                            <a href="{{ $leadUrl }}" wire:navigate class="min-w-0 truncate rounded-sm decoration-gray-400 underline-offset-[3px] transition-colors duration-150 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:decoration-gray-500">{!! $display !!}</a>
                        @else
                            <span class="min-w-0 truncate">{!! $display !!}</span>
                        @endif
                    </div>
                @elseif ($columnType === 'Aura\\Base\\Fields\\Email' && is_string($display))
                    {{-- Long addresses stay on one line; w-max makes the column claim the address width (up to 20rem) in the
                         auto table layout, so it is only cut when it is longer than that. The full address is in the tooltip. --}}
                    <span class="block w-max max-w-[20rem] truncate" title="{{ html_entity_decode(strip_tags($display)) }}">{!! $display !!}</span>
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
