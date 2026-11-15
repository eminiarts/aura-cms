@php
    $tableModel = $this->model;

    // Field type per visible column, so header, rows and the phone list can align and wrap by type.
    $visibleColumnKeys = collect($this->headers ?: [])->keys()->filter(fn ($key) => optional($this->columns)[$key])->values();
    $columnTypes = $visibleColumnKeys->mapWithKeys(fn ($key) => [$key => optional($tableModel->fieldBySlug($key))['type']])->all();

    $isEmpty = $rows->isEmpty();
    $isRelationTable = (bool) optional(optional($this)->field)['name'];
    $isFiltered = filled($this->search ?? null) || filled($this->selectedFilter ?? null) || collect(data_get($this->filters ?? [], 'custom', []))->isNotEmpty();
    $pluralLabel = Str::lower(__($tableModel->pluralName()));
    $singularLabel = Str::lower(__($tableModel->singularName()));

    // The stacked phone list mirrors the default row; a resource with its own row view keeps its table (scrollable).
    $stackOnMobile = ! $isEmpty && $this->settings['views']['row'] === 'aura::components.table.row';

    // Phone rows lead with the record's display name (the record page H1) when the resource defines one;
    // the generic "Type (#id)" fallback is no name, so those rows keep leading with their first column.
    $leadsWithTitle = $stackOnMobile && (new ReflectionMethod($tableModel, 'title'))->getDeclaringClass()->getName() !== \Aura\Base\Resource::class;
@endphp

<div class="flex flex-col mt-2" wire:key="table-index">
    {{-- No card around the list: a header hairline, row dividers and a closing hairline carry the table. --}}
    <div class="overflow-hidden">

        @if ($isEmpty)
            {{-- An empty table is still a table: from sm its real column header (no bulk checkbox, no sort),
                 then one left-aligned block between hairlines, a section title, one line and the action.
                 Phones get the same block without the header. Every empty list takes this shape: no
                 records yet, nothing matching the search or filters, an empty relation list. --}}
            @if ($this->headers && $visibleColumnKeys->isNotEmpty())
                <div class="hidden overflow-x-auto min-w-full align-middle sm:block">
                    <table class="min-w-full">
                        @include($this->settings['views']['table_header'])
                    </table>
                </div>
            @endif
            <div @class([
                'border-y border-aura-hairline sm:px-4',
                'sm:border-t-0' => $this->headers && $visibleColumnKeys->isNotEmpty(),
                $isRelationTable ? 'py-6' : 'py-10',
            ]) data-table-empty>
                @if ($isFiltered)
                    <h3 class="aura-section-title">{{ __('No matching :resources', ['resources' => $pluralLabel]) }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Try a different search or clear the filters.') }}</p>
                @else
                    @php
                        // A resource may word its own empty page (title, line, action) in aura/{type}/empty,
                        // like its header in aura/{type}/header; the block around it stays shared.
                        $emptyView = 'aura.'.Str::lower($tableModel->getType()).'.empty';
                        $emptyView = $isRelationTable ? null : (View::exists($emptyView) ? $emptyView : (View::exists('aura::'.$emptyView) ? 'aura::'.$emptyView : null));
                    @endphp

                    @if ($emptyView)
                        @include($emptyView)
                    @else
                        <h3 class="aura-section-title">{{ __('No :resources yet', ['resources' => $pluralLabel]) }}</h3>
                    @endif

                    @unless ($isRelationTable || $emptyView)
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Create the first one to get started.') }}</p>

                        @can('create', $tableModel)
                            @if ($this->settings['create'] && ! $this->settings['create_in_modal'] && $this->createLink)
                                <div class="mt-4">
                                    <x-aura::button href="{{ $this->createLink }}">
                                        <x-slot:icon>
                                            <x-aura::icon icon="plus" />
                                        </x-slot>
                                        <span>{{ __('New :resource', ['resource' => $singularLabel]) }}</span>
                                    </x-aura::button>
                                </div>
                            @endif
                        @endcan
                    @endunless
                @endif
            </div>
        @else
            <div @class(['overflow-x-auto min-w-full align-middle', 'hidden sm:block' => $stackOnMobile])>
                <table class="min-w-full">
                    @include($this->settings['views']['table_header'])

                    <tbody class="border-b divide-y divide-aura-hairline border-aura-hairline">
                        @foreach ($rows as $row)
                            @include($this->settings['views']['row'])
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($stackOnMobile)
                @php
                    // The whole row is one link, so links inside cell values (record chips, website hosts)
                    // become plain spans: nested anchors would split the row in the DOM.
                    $flattenLinks = fn ($html) => is_string($html)
                        ? preg_replace(['/<a\b([^>]*)>/i', '/<\/a>/i', '/\s(?:href|wire:navigate)(?:=("[^"]*"|\'[^\']*\'))?/i'], ['<span$1>', '</span>', ''], $html)
                        : $html;
                    $plainText = fn ($html) => is_scalar($html) ? trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES)) : '';
                    $isBlank = fn ($value) => $value === null || (is_string($value) && trim(strip_tags($value)) === '' && ! str_contains($value, '<svg'));

                    $mobileRows = $rows->map(function ($row) use ($visibleColumnKeys, $flattenLinks, $leadsWithTitle, $columnTypes, $tableModel) {
                        return [
                            'row' => $row,
                            'title' => $leadsWithTitle ? trim((string) $row->title()) : '',
                            'displays' => $visibleColumnKeys->mapWithKeys(fn ($key) => [$key => $flattenLinks(
                                ($columnTypes[$key] ?? null) === 'Aura\\Base\\Fields\\Number'
                                    ? \Aura\Base\Fields\Number::unitMarkup($tableModel->fieldBySlug($key) ?? [], $row->display($key))
                                    : $row->display($key)
                            )]),
                        ];
                    });

                    // A relation's name without its avatar initial.
                    $relationName = fn ($html) => is_string($html) && preg_match('/class=["\'][^"\']*aura-record-link-title[^"\']*["\'][^>]*>(.*?)<\/span>/s', $html, $match)
                        ? $plainText($match[1])
                        : $plainText($html);

                    // Two or more BelongsTo columns share one muted line under the name ("Northwind Software · Martina Keller"),
                    // as plain names, so the relations do not use up both meta lines. A single relation stays a regular meta column.
                    $relationKeys = $visibleColumnKeys
                        ->reject(fn ($key, $index) => ! $leadsWithTitle && $index === 0)
                        ->filter(fn ($key) => is_string($columnTypes[$key] ?? null) && is_a($columnTypes[$key], \Aura\Base\Fields\BelongsTo::class, true))
                        ->filter(fn ($key) => $mobileRows->contains(fn ($item) => $relationName($item['displays'][$key]) !== ''))
                        ->values();
                    $relationKeys = $relationKeys->count() > 1 ? $relationKeys : collect();

                    // A Boolean column with a date column named after it ("Published" and "Published at") reads as one
                    // state line on phones, "Published · Sep 6, 2026" or "Not published", instead of a bare check icon.
                    $isType = fn ($key, $class) => is_string($columnTypes[$key] ?? null) && is_a($columnTypes[$key], $class, true);
                    $columnLabel = fn ($key) => trim((string) ($this->headers[$key] ?? ''));
                    $stateKeys = null;
                    foreach ($visibleColumnKeys as $index => $boolKey) {
                        if ((! $leadsWithTitle && $index === 0) || ! $isType($boolKey, \Aura\Base\Fields\Boolean::class) || $columnLabel($boolKey) === '') {
                            continue;
                        }
                        $dateKey = $visibleColumnKeys->first(fn ($key) => ($isType($key, \Aura\Base\Fields\Date::class) || $isType($key, \Aura\Base\Fields\Datetime::class))
                            && Str::startsWith(Str::lower($columnLabel($key)), Str::lower($columnLabel($boolKey)).' '));
                        if ($dateKey) {
                            $stateKeys = [$boolKey, $dateKey];
                            break;
                        }
                    }

                    // The meta lines show the same columns on every row: the two most filled other columns on this page
                    // (in column order), so one row cannot show a company where the next shows a phone number.
                    // Columns the record name already contains (first and last name) and the leading column stay out.
                    $metaKeys = $visibleColumnKeys
                        ->reject(fn ($key, $index) => ! $leadsWithTitle && $index === 0)
                        ->reject(fn ($key) => $relationKeys->contains($key))
                        ->reject(fn ($key) => in_array($key, $stateKeys ?? [], true))
                        ->reject(fn ($key) => $leadsWithTitle && $mobileRows->every(function ($item) use ($key, $plainText) {
                            $text = $plainText($item['displays'][$key]);

                            return $text === '' || $item['title'] === ''
                                || preg_match('/(?<![\p{L}\p{N}])'.preg_quote($text, '/').'(?![\p{L}\p{N}])/iu', $item['title']) === 1;
                        }))
                        ->map(fn ($key) => ['key' => $key, 'filled' => $mobileRows->reject(fn ($item) => $isBlank($item['displays'][$key]))->count()])
                        ->filter(fn ($column) => $column['filled'] > 0)
                        ->sortByDesc('filled')
                        ->take(2)
                        ->pluck('key')
                        ->sortBy(fn ($key) => $visibleColumnKeys->search($key))
                        ->values();

                    // A Number and a Status column share one line: the tag left, the value right-aligned
                    // (a deal's stage and amount), so such rows take three lines instead of four.
                    $metaKeyOfType = fn ($class) => $metaKeys->first(fn ($key) => is_string($columnTypes[$key] ?? null) && is_a($columnTypes[$key], $class, true));
                    $numberKey = $metaKeyOfType(\Aura\Base\Fields\Number::class);
                    $statusKey = $metaKeyOfType(\Aura\Base\Fields\Status::class);
                    $pairedKeys = ($numberKey && $statusKey) ? [$statusKey, $numberKey] : [];
                @endphp
                {{-- Phones: one line for the record, its relations on one line when it has several, then the same two columns on every row (a status and a number share one line), then a Boolean state with its date. --}}
                <ul class="border-t border-b divide-y divide-aura-hairline border-aura-hairline sm:hidden">
                    @foreach ($mobileRows as $item)
                        @php
                            $row = $item['row'];
                            $displays = $item['displays'];
                            $rowTitle = $item['title'];
                            $primary = $rowTitle !== '' ? e($rowTitle) : $displays->first();
                            $hasAvatar = method_exists($row, 'avatarShape') && $row->avatarShape();
                            $metaValues = $metaKeys->reject(fn ($key) => in_array($key, $pairedKeys, true))->map(fn ($key) => $displays[$key])->values();
                            $relationLine = $relationKeys->map(fn ($key) => $relationName($displays[$key]))->filter(fn ($name) => $name !== '')->implode(' · ');
                            $rowUrl = auth()->user()?->can('view', $row) ? $row->viewUrl() : null;
                        @endphp
                        <li wire:key="mobile-row-{{ $row->id }}">
                            <{{ $rowUrl ? 'a' : 'div' }} @if ($rowUrl) href="{{ $rowUrl }}" wire:navigate @endif
                                class="flex gap-3 items-center py-3 {{ $rowUrl ? 'transition-colors duration-150 active:bg-gray-50 dark:active:bg-white/[0.04] focus:outline-none focus-visible:bg-gray-50 dark:focus-visible:bg-white/[0.04]' : '' }}">
                                @if ($hasAvatar)
                                    <x-aura::avatar :record="$row" size="md" />
                                @endif
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate dark:text-white">
                                        @if ($primary === null || $primary === '')
                                            <span class="text-gray-400 dark:text-gray-500">–</span>
                                        @else
                                            {!! $primary !!}
                                        @endif
                                    </div>
                                    @if ($relationKeys->isNotEmpty())
                                        <div class="mt-0.5 h-5 text-sm text-gray-500 truncate dark:text-gray-400" data-mobile-row-relations>
                                            @if ($relationLine === '')
                                                <span class="text-gray-400 dark:text-gray-500">–</span>
                                            @else
                                                {{ $relationLine }}
                                            @endif
                                        </div>
                                    @endif
                                    @if ($pairedKeys !== [])
                                        <div class="flex gap-3 justify-between items-center mt-1 min-w-0 h-5 text-sm text-gray-500 whitespace-nowrap dark:text-gray-400" data-mobile-row-pair>
                                            @foreach ($pairedKeys as $pairedKey)
                                                @if ($isBlank($displays[$pairedKey]))
                                                    <span class="text-gray-400 dark:text-gray-500">–</span>
                                                @else
                                                    <span @class(['min-w-0 truncate', 'tabular-nums shrink-0' => $pairedKey === $numberKey])>{!! $displays[$pairedKey] !!}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                    {{-- Plain text meta values share one quiet line, joined with " · ", and wrap onto a second
                                         line only when they do not fit (.aura-dot-list drops the dot at a line start). A relation
                                         chip or a tag always takes a line of its own, so rows keep one rhythm whatever their data.
                                         Blank values drop out; a row with none keeps one line as a dash. --}}
                                    @if ($metaValues->isNotEmpty())
                                        @php
                                            $filledMeta = $metaValues->reject(fn ($value) => $isBlank($value));
                                        @endphp
                                        <div class="mt-0.5 text-sm text-gray-500 dark:text-gray-400 [&_.aura-link]:text-inherit [&_.aura-record-link]:text-inherit" data-mobile-row-meta>
                                            @if ($filledMeta->isEmpty())
                                                <div class="h-5 text-gray-400 dark:text-gray-500">–</div>
                                            @else
                                                <div class="aura-dot-list">
                                                    @foreach ($filledMeta as $metaValue)
                                                        <span @class(['aura-dot-list-item', 'aura-dot-list-item-line' => preg_match('/class=["\'][^"\']*\baura-(record-link|tag)\b/', (string) $metaValue) === 1])><span class="min-w-0 truncate">{!! $metaValue !!}</span></span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($stateKeys)
                                        @php
                                            [$stateBool, $stateDate] = $stateKeys;
                                            $stateOn = (bool) $row->{$stateBool};
                                            $stateDateText = $stateOn ? $plainText($displays[$stateDate]) : '';
                                        @endphp
                                        <div class="mt-0.5 text-sm text-gray-500 truncate dark:text-gray-400" data-mobile-row-state>
                                            @if ($stateOn)
                                                <span class="text-gray-700 dark:text-gray-300">{{ $columnLabel($stateBool) }}</span>@if ($stateDateText !== '' && $stateDateText !== '–') &middot; {{ $stateDateText }}@endif
                                            @else
                                                {{ __('Not :state', ['state' => Str::lower($columnLabel($stateBool))]) }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                @if ($rowUrl)
                                    <x-aura::icon.caret direction="right" class="w-4 h-4 text-gray-400 shrink-0" />
                                @endif
                            </{{ $rowUrl ? 'a' : 'div' }}>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif

    </div>

    @include($this->settings['views']['table_footer'])

</div>
