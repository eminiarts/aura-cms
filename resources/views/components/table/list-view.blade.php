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
                    <h3 class="aura-section-title">{{ __('No :resources yet', ['resources' => $pluralLabel]) }}</h3>

                    @unless ($isRelationTable)
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
                {{-- Phones: one line for the record, one quiet line with the next two columns. --}}
                <ul class="border-t border-b divide-y divide-gray-200/60 border-gray-200/80 sm:hidden dark:divide-white/[0.06] dark:border-white/10">
                    @foreach ($rows as $row)
                        @php
                            // The whole row is one link, so links inside cell values (record chips, website hosts)
                            // become plain spans: nested anchors would split the row in the DOM.
                            $flattenLinks = fn ($html) => is_string($html)
                                ? preg_replace(['/<a\b([^>]*)>/i', '/<\/a>/i', '/\s(?:href|wire:navigate)(?:=("[^"]*"|\'[^\']*\'))?/i'], ['<span$1>', '</span>', ''], $html)
                                : $html;
                            $displays = $visibleColumnKeys->mapWithKeys(fn ($key) => [$key => $flattenLinks($row->display($key))]);
                            $plainText = fn ($html) => is_scalar($html) ? trim(html_entity_decode(strip_tags((string) $html), ENT_QUOTES)) : '';
                            $rowTitle = $leadsWithTitle ? trim((string) $row->title()) : '';

                            if ($rowTitle !== '') {
                                // "Hannah Schmid" leads; columns it already contains (first and last name) leave the meta line.
                                $primary = e($rowTitle);
                                $metaDisplays = $displays->reject(function ($html) use ($plainText, $rowTitle) {
                                    $text = $plainText($html);

                                    return $text !== '' && preg_match('/(?<![\p{L}\p{N}])'.preg_quote($text, '/').'(?![\p{L}\p{N}])/iu', $rowTitle) === 1;
                                });
                            } else {
                                $primary = $displays->first();
                                $metaDisplays = $displays->slice(1);
                            }

                            $hasAvatar = method_exists($row, 'avatarShape') && $row->avatarShape();
                            $metaValues = $metaDisplays
                                ->filter(fn ($value) => ! ($value === null || (is_string($value) && trim(strip_tags($value)) === '' && ! str_contains($value, '<svg'))))
                                ->take(2);
                            $rowUrl = auth()->user()?->can('view', $row) ? $row->viewUrl() : null;
                        @endphp
                        <li wire:key="mobile-row-{{ $row->id }}">
                            <{{ $rowUrl ? 'a' : 'div' }} @if ($rowUrl) href="{{ $rowUrl }}" wire:navigate @endif
                                class="flex gap-3 items-center px-4 py-3 {{ $rowUrl ? 'transition-colors duration-150 active:bg-gray-50 dark:active:bg-white/[0.04] focus:outline-none focus-visible:bg-gray-50 dark:focus-visible:bg-white/[0.04]' : '' }}">
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
                                    @if ($metaValues->isNotEmpty())
                                        {{-- One line only: a value that does not fit wraps onto a second line, which max-h hides,
                                             so a long email shows alone (truncated) instead of two cut-off values. --}}
                                        <div class="flex overflow-hidden flex-wrap items-center mt-1 max-h-6 text-sm text-gray-500 whitespace-nowrap dark:text-gray-400">
                                            @foreach ($metaValues as $metaValue)
                                                <span class="inline-flex items-center max-w-full min-w-0 h-6">
                                                    @if (! $loop->first)
                                                        <span class="px-2 text-gray-300 dark:text-gray-600" aria-hidden="true">&middot;</span>
                                                    @endif
                                                    <span class="min-w-0 truncate">{!! $metaValue !!}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
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
