@php
    $tableModel = $this->model;

    // Field type per visible column, so header, rows and the phone list can align and wrap by type.
    $visibleColumnKeys = collect($this->headers ?: [])->keys()->filter(fn ($key) => optional($this->columns)[$key])->values();
    $columnTypes = $visibleColumnKeys->mapWithKeys(fn ($key) => [$key => optional($tableModel->fieldBySlug($key))['type']])->all();

    $isEmpty = $rows->isEmpty();
    $isRelationTable = (bool) optional(optional($this)->field)['name'];
    $isFiltered = filled($this->search ?? null) || collect(data_get($this->filters ?? [], 'custom', []))->isNotEmpty();
    $pluralLabel = Str::lower(__($tableModel->pluralName()));
    $singularLabel = Str::lower(__($tableModel->singularName()));

    // The stacked phone list mirrors the default row; a resource with its own row view keeps its table (scrollable).
    $stackOnMobile = ! $isEmpty && $this->settings['views']['row'] === 'aura::components.table.row';
@endphp

<div class="flex flex-col mt-2" wire:key="table-index">
    <div class="overflow-hidden bg-white rounded-xl ring-1 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">

        @if ($isEmpty)
            <div class="flex flex-col items-center px-6 text-center {{ $isRelationTable ? 'py-8' : 'py-14' }}">
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

                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($rows as $row)
                            @include($this->settings['views']['row'])
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($stackOnMobile)
                {{-- Phones: one line for the record, one quiet line with the next two columns. --}}
                <ul class="divide-y divide-gray-100 sm:hidden dark:divide-white/5">
                    @foreach ($rows as $row)
                        @php
                            $primaryKey = $visibleColumnKeys->first();
                            $primary = $primaryKey ? $row->display($primaryKey) : null;
                            $metaValues = $visibleColumnKeys->slice(1)
                                ->map(fn ($key) => $row->display($key))
                                ->filter(fn ($value) => ! ($value === null || (is_string($value) && trim(strip_tags($value)) === '' && ! str_contains($value, '<svg'))))
                                ->take(2);
                            $rowUrl = auth()->user()?->can('view', $row) ? $row->viewUrl() : null;
                        @endphp
                        <li wire:key="mobile-row-{{ $row->id }}">
                            <{{ $rowUrl ? 'a' : 'div' }} @if ($rowUrl) href="{{ $rowUrl }}" wire:navigate @endif
                                class="flex gap-3 items-center px-4 py-3 {{ $rowUrl ? 'transition-colors duration-150 active:bg-gray-50 dark:active:bg-white/[0.04] focus:outline-none focus-visible:bg-gray-50 dark:focus-visible:bg-white/[0.04]' : '' }}">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate dark:text-white">
                                        @if ($primary === null || $primary === '')
                                            <span class="text-gray-400 dark:text-gray-500">–</span>
                                        @else
                                            {!! $primary !!}
                                        @endif
                                    </div>
                                    @if ($metaValues->isNotEmpty())
                                        <div class="flex overflow-hidden gap-x-2 items-center mt-1 text-sm text-gray-500 whitespace-nowrap dark:text-gray-400">
                                            @foreach ($metaValues as $metaValue)
                                                @if (! $loop->first)
                                                    <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">&middot;</span>
                                                @endif
                                                <span class="min-w-0 truncate">{!! $metaValue !!}</span>
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
