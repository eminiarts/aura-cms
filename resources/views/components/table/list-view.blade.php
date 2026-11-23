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
    $hasOwnTitle = (new ReflectionMethod($tableModel, 'title'))->getDeclaringClass()->getName() !== \Aura\Base\Resource::class;
    $leadsWithTitle = $stackOnMobile && $hasOwnTitle;
@endphp

<div class="flex flex-col mt-2" wire:key="table-index">
    {{-- No card around the list: a header hairline, row dividers and a closing hairline carry the table. --}}
    <div class="overflow-hidden">

        @if ($isEmpty)
            {{-- A page with no records at all has no toolbar above, so no hairline either; lists keep it. --}}
            <div @class([
                'flex flex-col items-center px-6 text-center',
                'border-t border-gray-200/80 dark:border-white/10' => $isRelationTable || $isFiltered,
                $isRelationTable ? 'py-8' : 'py-14',
            ])>
                @if ($isFiltered)
                    <h3 class="aura-section-title">{{ __('No matching :resources', ['resources' => $pluralLabel]) }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Try a different search or clear the filters.') }}</p>
                @else
                    @unless ($isRelationTable)
                        <div class="flex justify-center items-center mb-3 w-10 h-10 rounded-lg bg-gray-950/[0.04] text-gray-400 dark:bg-white/5 dark:text-gray-500 [&>svg]:size-5" aria-hidden="true">
                            {!! $tableModel->getIcon() !!}
                        </div>
                    @endunless
                    @php
                        // A resource may word its own empty page (title, line, action) in aura/{type}/empty,
                        // like its header in aura/{type}/header; the icon box above stays shared.
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
                                <div class="mt-5">
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

                    <tbody class="border-b divide-y divide-gray-200/60 border-gray-200/80 dark:divide-white/[0.06] dark:border-white/10">
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

                    $mobileRows = $rows->map(function ($row) use ($visibleColumnKeys, $flattenLinks, $leadsWithTitle) {
                        return [
                            'row' => $row,
                            'title' => $leadsWithTitle ? trim((string) $row->title()) : '',
                            'displays' => $visibleColumnKeys->mapWithKeys(fn ($key) => [$key => $flattenLinks($row->display($key))]),
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

                    // The meta lines show the same columns on every row: the two most filled other columns on this page
                    // (in column order), so one row cannot show a company where the next shows a phone number.
                    // Columns the record name already contains (first and last name) and the leading column stay out.
                    $metaKeys = $visibleColumnKeys
                        ->reject(fn ($key, $index) => ! $leadsWithTitle && $index === 0)
                        ->reject(fn ($key) => $relationKeys->contains($key))
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
                {{-- Phones: one line for the record, its relations on one line when it has several, then the same two columns on every row, one per line (a status and a number share one). --}}
                <ul class="border-t border-b divide-y divide-gray-200/60 border-gray-200/80 sm:hidden dark:divide-white/[0.06] dark:border-white/10">
                    @foreach ($mobileRows as $item)
                        @php
                            $row = $item['row'];
                            $displays = $item['displays'];
                            $rowTitle = $item['title'];
                            $primary = $rowTitle !== '' ? e($rowTitle) : $displays->first();
                            $hasAvatar = method_exists($row, 'avatarShape') && $row->avatarShape();
                            // A blank value keeps its line as a dash, so every row has the same lines and height.
                            $metaValues = $metaKeys->reject(fn ($key) => in_array($key, $pairedKeys, true))->map(fn ($key) => $displays[$key])->values();
                            $relationLine = $relationKeys->map(fn ($key) => $relationName($displays[$key]))->filter(fn ($name) => $name !== '')->implode(' · ');
                            $rowUrl = auth()->user()?->can('view', $row) ? $row->viewUrl() : null;
                        @endphp
                        <li wire:key="mobile-row-{{ $row->id }}">
                            <{{ $rowUrl ? 'a' : 'div' }} @if ($rowUrl) href="{{ $rowUrl }}" wire:navigate @endif
                                class="flex gap-3 items-center py-3 {{ $rowUrl ? 'transition-colors duration-150 active:bg-gray-50 dark:active:bg-white/[0.04] focus:outline-none focus-visible:bg-gray-50 dark:focus-visible:bg-white/[0.04]' : '' }}">
                                @if ($hasAvatar && $hasOwnTitle)
                                    <x-aura::avatar :record="$row" size="md" />
                                @elseif ($hasAvatar)
                                    {{-- Generic "Type (#id)" title: initials and tint from the row's leading text, as in the desktop row. --}}
                                    <x-aura::avatar :name="$plainText($primary)" :shape="$row->avatarShape()" :image-id="$row->avatarImageId()" size="md" />
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
                                    {{-- One quiet line per meta value: two values side by side would both cut off on a phone. --}}
                                    @foreach ($metaValues as $metaValue)
                                        <div class="flex items-center mt-0.5 min-w-0 h-5 text-sm text-gray-500 whitespace-nowrap dark:text-gray-400 [&_.aura-link]:text-inherit [&_.aura-record-link]:text-inherit">
                                            @if ($isBlank($metaValue))
                                                <span class="text-gray-400 dark:text-gray-500">–</span>
                                            @else
                                                <span class="min-w-0 truncate">{!! $metaValue !!}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                @if ($rowUrl)
                                    <svg class="w-4 h-4 text-gray-300 shrink-0 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
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
