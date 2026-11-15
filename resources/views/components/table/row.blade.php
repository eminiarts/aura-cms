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
            {{-- A selected row carries the mark (one Λ leg), like the active sidebar item. --}}
            <span x-show="selected.includes('{{ $row->id }}')" x-cloak
                  class="absolute inset-y-2 left-0 aura-mark bg-primary-600 dark:bg-primary-400" aria-hidden="true"></span>
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
                $columnField = ($tableModel ?? $this->model)->fieldBySlug($key) ?? [];
                // A JSON value reads as one mono line (Json::listPreview()), not as collapsed pretty-printed JSON.
                $display = (($columnTypes[$key] ?? null) === 'Aura\\Base\\Fields\\Json' && $columnField
                    ? \Aura\Base\Fields\Json::listPreview($columnField, $row) ?? $row->display($key)
                    : $row->display($key));
                // Empty cells show the same dash as empty view values.
                $displayIsEmpty = $display === null || $display === '' || (is_string($display) && trim($display) === '');
                $columnType = $columnTypes[$key] ?? null;
                // A number's unit (prefix or suffix) reads quieter than the figure.
                if ($columnType === 'Aura\\Base\\Fields\\Number') {
                    $display = \Aura\Base\Fields\Number::unitMarkup(($tableModel ?? $this->model)->fieldBySlug($key) ?? [], $display);
                }
                // A related list (a section of a record page) is narrow: its dates read "Nov 27" in the current year,
                // with the full date in the tooltip, so the columns fit the record's main column.
                if (($isRelationTable ?? false) && ! $displayIsEmpty && ! isset($columnField['display']) && is_string($columnType)
                    && (is_a($columnType, \Aura\Base\Fields\Date::class, true) || is_a($columnType, \Aura\Base\Fields\Datetime::class, true))) {
                    try {
                        $shortDate = \Illuminate\Support\Carbon::parse($row->{$key});
                        $display = '<time datetime="'.e($shortDate->toDateString()).'" title="'.e($shortDate->format('M j, Y')).'">'
                            .e($shortDate->format($shortDate->year === now()->year ? 'M j' : 'M j, Y')).'</time>';
                    } catch (\Throwable $e) {
                        // An unparsable stored value keeps the field's own display.
                    }
                }
                // A Boolean column with a date column named after it ("Published" and "Published at") reads as a
                // word, "Published" or "Not published", as the phone list says it, instead of a check or a faint cross.
                $isStateColumn = is_string($columnType) && is_a($columnType, \Aura\Base\Fields\Boolean::class, true)
                    && trim((string) $column) !== ''
                    && ! isset($columnField['display'])
                    && collect($this->headers)->contains(fn ($label, $otherKey) => is_string($columnTypes[$otherKey] ?? null)
                        && (is_a($columnTypes[$otherKey], \Aura\Base\Fields\Date::class, true) || is_a($columnTypes[$otherKey], \Aura\Base\Fields\Datetime::class, true))
                        && Str::startsWith(Str::lower(trim((string) $label)), Str::lower(trim((string) $column)).' '));
                $cellClass = match ($columnType) {
                    'Aura\\Base\\Fields\\Number' => 'text-right whitespace-nowrap',
                    'Aura\\Base\\Fields\\Phone', 'Aura\\Base\\Fields\\Date', 'Aura\\Base\\Fields\\Datetime' => 'whitespace-nowrap',
                    default => '',
                };
            @endphp
            <td class="px-4 py-[var(--aura-row-padding-y,0.625rem)] text-sm {{ $cellClass }} {{ $firstVisibleColumn ? 'font-medium text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300' }}">
                @if ($isStateColumn && ! $firstVisibleColumn)
                    @if ($row->{$key})
                        <span class="text-gray-700 dark:text-gray-300" data-state-value="true">{{ __($column) }}</span>
                    @else
                        <span class="text-gray-500 dark:text-gray-400" data-state-value="false">{{ __('Not :state', ['state' => Str::lower(__($column))]) }}</span>
                    @endif
                @elseif ($displayIsEmpty)
                    <span class="text-gray-400 dark:text-gray-500">–</span>
                @elseif ($firstVisibleColumn)
                    {{-- Leading column is the record: its avatar (when the resource has one), the name, and a link
                         to the record when the viewer may open it. No link around a value that is itself a link. --}}
                    @php
                        $hasAvatar = method_exists($row, 'avatarShape') && $row->avatarShape();
                        $leadUrl = ! (is_string($display) && stripos($display, '<a') !== false) ? $rowViewUrl : null;
                    @endphp
                    <div @class(['flex gap-2.5 items-center min-w-0', 'max-w-[11rem]' => $isRelationTable ?? false])>
                        @if ($hasAvatar)
                            <x-aura::avatar :record="$row" size="sm" />
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
                    <span @class(['block w-max truncate', ($isRelationTable ?? false) ? 'max-w-[13rem]' : 'max-w-[20rem]']) title="{{ html_entity_decode(strip_tags($display)) }}">{!! $display !!}</span>
                @elseif ($columnType === 'Aura\\Base\\Fields\\Text' && is_string($display) && ! str_contains($display, '<'))
                    {{-- A short text (a job title, a city) keeps one line, up to 16rem (10rem in a related list), then ends in an ellipsis with the
                         full value in the tooltip, so rows keep one height instead of breaking "Head of / Finance". --}}
                    <span @class(['block w-max truncate', ($isRelationTable ?? false) ? 'max-w-[10rem]' : 'max-w-[16rem]']) title="{{ html_entity_decode($display, ENT_QUOTES) }}">{!! $display !!}</span>
                @else
                    {!! $display !!}
                @endif
            </td>
            @php $firstVisibleColumn = false; @endphp
        @endif
    @endforeach
    @endif

    @if ($this->settings['actions'])
        <td class="px-3 py-2 table-row-actions-cell">
            @include('aura::components.table.row-actions')
        </td>
    @endif
</tr>
