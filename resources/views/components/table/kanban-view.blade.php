{{-- resources/views/components/table/kanban-view.blade.php

     The package board, one column per declared option of the group field, in the board pattern the
     styleguide documents (.aura-board-*): a quiet column panel with a title of dot, name and count, then
     linked cards. Columns share the content width (five fit at 1440) and scroll sideways below that, with
     a fade on the right edge while more columns sit off screen. Phones show one column at a time; a
     segmented switcher with counts above the board picks it.

     Opt-in kanbanSettings() keys:
     - column_total: a number field summed over the column's cards, right in the column title. Hidden
       while the board has more pages, because the sum would only cover the loaded cards.
     - card_fields: slugs shown on each card. A relation to a user is a 20px avatar beside the title, any
       other relation a chip with the record's avatar under it, then one meta row: values first (a number's
       unit quieter, as in the table), dates "Nov 17" with a calendar mark on the right. --}}
@php
    $kanban = $kanban ?? $this->resolvedKanbanConfiguration();
    $groupField = $kanban['group_field'] ?? 'status';
    $cardTitle = $kanban['card_title'] ?? 'title';
    $cardSubtitle = $kanban['card_subtitle'] ?? null;
    $showEmptyColumns = $kanban['show_empty_columns'] ?? true;
    $columnTotal = $kanban['column_total'] ?? null;
    $cardFields = $kanban['card_fields'] ?? [];
    $items = method_exists($rows, 'items') ? collect($rows->items()) : collect($rows);
    $hasMorePages = method_exists($rows, 'hasMorePages') && $rows->hasMorePages();

    $columns = collect($this->kanbanStatuses)
        ->map(fn ($column, $key) => $column + [
            'rows' => $items->filter(fn ($row) => (string) data_get($row, $groupField) === (string) $key),
        ])
        ->filter(fn ($column) => ($column['visible'] ?? true) && ($showEmptyColumns || $column['rows']->isNotEmpty()));

    $display = function ($row, string $slug) {
        $value = method_exists($row, 'display') ? $row->display($slug) : e(data_get($row, $slug));
        $field = $row->fieldBySlug($slug);

        return is_a($field['type'] ?? '', \Aura\Base\Fields\Number::class, true) ? \Aura\Base\Fields\Number::unitMarkup($field, $value) : $value;
    };

    // What each card field is: person (a user relation), relation, date or value.
    $userResource = config('aura.resources.user', \Aura\Base\Resources\User::class);
    $roles = collect($cardFields)->mapWithKeys(function ($slug) use ($model, $userResource) {
        $type = $model->fieldBySlug($slug)['type'] ?? '';
        $resource = $model->fieldBySlug($slug)['resource'] ?? null;

        return [$slug => match (true) {
            is_a($type, \Aura\Base\Fields\BelongsTo::class, true) && is_string($resource) && (is_a($resource, \Aura\Base\Resources\User::class, true) || is_a($resource, $userResource, true)) => 'person',
            is_a($type, \Aura\Base\Fields\BelongsTo::class, true) => 'relation',
            is_a($type, \Aura\Base\Fields\Date::class, true) || is_a($type, \Aura\Base\Fields\Datetime::class, true) => 'date',
            default => 'value',
        }];
    });

    // The related records of every relation card field, one query per field.
    $related = $roles->filter(fn ($role) => in_array($role, ['person', 'relation'], true))
        ->map(function ($role, $slug) use ($model, $items) {
            $resource = $model->fieldBySlug($slug)['resource'] ?? null;
            $ids = $items->map(fn ($row) => data_get($row, $slug))->filter(fn ($id) => is_scalar($id) && $id !== '')->unique()->values();

            if (! is_string($resource) || ! class_exists($resource) || $ids->isEmpty()) {
                return collect();
            }

            $instance = app($resource);

            return $instance->newQuery()->whereIn($instance->getQualifiedKeyName(), $ids)->get()->keyBy($instance->getKeyName());
        });

    $columnSum = function ($columnRows) use ($columnTotal, $model) {
        $field = $model->fieldBySlug($columnTotal);
        $sum = $columnRows->sum(fn ($row) => (float) data_get($row, $columnTotal));
        $fieldClass = $model->fieldClassBySlug($columnTotal);
        $value = is_object($fieldClass) && method_exists($fieldClass, 'display') ? $fieldClass->display($field, $sum, $model) : number_format($sum);

        return is_a($field['type'] ?? '', \Aura\Base\Fields\Number::class, true) ? \Aura\Base\Fields\Number::unitMarkup($field, $value) : e($value);
    };

    // Column dots use the option's hue at -500 (dark -400): its -800 text color reads black at 8px. Gray stays a quiet -400.
    // An option color may be classes ("bg-blue-100 text-blue-800") or a bare hue ("blue").
    // Hues: text-blue-500 text-yellow-500 text-green-500 text-red-500 text-amber-500 text-orange-500 text-purple-500 text-indigo-500 text-teal-500 text-pink-500
    // dark:text-blue-400 dark:text-yellow-400 dark:text-green-400 dark:text-red-400 dark:text-amber-400 dark:text-orange-400 dark:text-purple-400 dark:text-indigo-400 dark:text-teal-400 dark:text-pink-400
    $dotColor = function ($color) {
        $color = (string) $color;
        $hue = preg_match('/(?:^|\s)(?:text|bg)-([a-z]+)-\d+/', $color, $match) ? $match[1] : (preg_match('/^[a-z]+$/', $color) ? $color : 'gray');

        return in_array($hue, ['gray', 'slate', 'zinc', 'neutral', 'stone'], true)
            ? 'text-gray-400 dark:text-gray-500'
            : "text-{$hue}-500 dark:text-{$hue}-400";
    };

    $segmentBase = 'inline-flex flex-auto gap-1 justify-center items-center px-1.5 h-8 text-[13px] font-medium rounded-md whitespace-nowrap transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500';
    $segmentActive = 'bg-white text-gray-900 shadow-xs ring-1 ring-gray-950/[0.07] dark:bg-gray-700 dark:text-white dark:ring-white/10';
    $segmentIdle = 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200';
    $today = today();
@endphp

<div class="relative mt-5 kanban-board" wire:key="kanban-board-{{ $model->getType() }}"
    x-data="{
        more: false,
        current: 0,
        update() {
            const el = this.$refs.board;
            this.more = el.scrollLeft + el.clientWidth < el.scrollWidth - 1;
        },
    }" x-init="update()" x-on:resize.window="update()">
    {{-- Phones: one column at a time; tapping a segment shows its column. --}}
    <div class="mb-4 sm:hidden" data-board-switcher>
        <div class="flex gap-0.5 items-center p-0.5 w-full rounded-lg bg-gray-950/[0.04] dark:bg-white/5" role="tablist" aria-label="{{ __($model->fieldBySlug($groupField)['name'] ?? 'Status') }}">
            @foreach ($columns->values() as $index => $column)
                <button type="button" role="tab" x-on:click="current = {{ $index }}"
                    x-bind:aria-selected="current === {{ $index }} ? 'true' : 'false'"
                    x-bind:class="current === {{ $index }} ? @js($segmentActive) : @js($segmentIdle)"
                    class="{{ $segmentBase }} {{ $index === 0 ? $segmentActive : $segmentIdle }}">
                    {{ __($column['value'] ?? '') }}
                    <span class="font-normal text-gray-400 tabular-nums dark:text-gray-500">{{ $column['rows']->count() }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <div x-ref="board" x-on:scroll.passive="update()" class="grid sm:grid-flow-col sm:auto-cols-[minmax(13rem,1fr)] sm:gap-x-3 sm:overflow-x-auto sm:pb-3 sm:px-px sm:-mx-px">
        @foreach ($columns as $columnKey => $column)
            @php
                $index = $loop->index;
                $columnRows = $column['rows'];
                $total = $columnTotal && ! $hasMorePages ? $columnSum($columnRows) : null;
            @endphp
            <section class="flex flex-col min-w-0 kanban-column aura-board-column {{ $index === 0 ? '' : 'max-sm:hidden' }}"
                x-bind:class="{ 'max-sm:hidden': current !== {{ $index }} }"
                aria-label="{{ __($column['value'] ?? $columnKey) }}"
                data-board-column
                data-kanban-column="{{ $columnKey }}"
                wire:key="kanban-column-{{ $model->getType() }}-{{ $columnKey }}">
                <h3 class="max-sm:sr-only aura-board-column-title">
                    <svg class="size-2 shrink-0 fill-current {{ $dotColor($column['color'] ?? '') }}" viewBox="0 0 8 8" aria-hidden="true"><circle cx="4" cy="4" r="4" /></svg>
                    <span class="truncate shrink-0 max-w-[60%]">{{ __($column['value'] ?? $columnKey) }}</span>
                    <span class="aura-board-column-count">{{ $columnRows->count() }}</span>
                    @if ($total !== null)
                        <span class="ml-auto min-w-0 truncate aura-board-column-total" data-kanban-column-total>{!! $total !!}</span>
                    @endif
                </h3>

                @if ($total !== null && $columnRows->isNotEmpty())
                    {{-- Phones: the title is in the switcher, so the column's sum gets its own line. --}}
                    <p class="px-1 pb-2 text-[13px] text-gray-500 sm:hidden dark:text-gray-400">{{ __('Total') }} <span class="aura-board-column-total">{!! $total !!}</span></p>
                @endif

                <div class="space-y-2">
                    @forelse ($columnRows as $row)
                        @php
                            $url = $row->viewUrl() ?? $row->editUrl();
                            $chips = [];
                            $meta = [];
                            $person = null;

                            foreach ($roles as $slug => $role) {
                                $value = data_get($row, $slug);

                                if ($value === null || $value === '') {
                                    continue;
                                }

                                if ($role === 'person') {
                                    $person ??= $related[$slug]->get($value);
                                } elseif ($role === 'relation') {
                                    if ($record = $related[$slug]->get($value)) {
                                        $chips[] = $record;
                                    }
                                } elseif ($role === 'date') {
                                    try {
                                        $date = \Illuminate\Support\Carbon::parse($value);
                                        $meta[] = ['date' => $date, 'slug' => $slug];
                                    } catch (\Throwable $e) {
                                        // An unparsable date stays off the card.
                                    }
                                } else {
                                    $meta[] = ['html' => $display($row, $slug)];
                                }
                            }
                        @endphp
                        <a @if ($url) href="{{ $url }}" wire:navigate @endif class="aura-board-card"
                            data-kanban-card="{{ $row->getKey() }}"
                            wire:key="kanban-card-{{ $model->getType() }}-{{ $row->getKey() }}">
                            {{-- The owner's avatar sits beside the title, so the meta row keeps its width for the figures. --}}
                            <div class="flex gap-2 items-start">
                                <h4 class="flex-1 min-w-0 aura-board-card-title">{!! $display($row, $cardTitle) !!}</h4>
                                @if ($person)
                                    <x-aura::avatar :record="$person" size="xs" title="{{ $person->title() }}" class="mt-px" />
                                @endif
                            </div>

                            @if ($cardSubtitle)
                                <div class="aura-board-card-body">{!! $display($row, $cardSubtitle) !!}</div>
                            @endif

                            @foreach ($chips as $record)
                                <div class="flex gap-1.5 items-center mt-2 min-w-0 text-gray-600 dark:text-gray-300" data-kanban-card-relation>
                                    <x-aura::avatar :record="$record" size="xs" />
                                    <span class="truncate">{{ $record->title() }}</span>
                                </div>
                            @endforeach

                            @if ($meta !== [])
                                <div class="aura-board-card-meta">
                                    @foreach ($meta as $item)
                                        @if (isset($item['date']))
                                            {{-- A date near today drops the year ("Jan 14"); the full date is in the tooltip. --}}
                                            <time datetime="{{ $item['date']->toDateString() }}" title="{{ $model->fieldBySlug($item['slug'])['name'] ?? '' }} · {{ $item['date']->format('M j, Y') }}" @class(['inline-flex gap-1 items-center whitespace-nowrap tabular-nums', 'ml-auto' => $loop->index > 0 && ! isset($meta[$loop->index - 1]['date'])])>
                                                <svg class="text-gray-400 size-3.5 shrink-0 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <rect x="3.5" y="5" width="17" height="15.5" rx="2.5" />
                                                    <path d="M3.5 10h17M8 3v4M16 3v4" />
                                                </svg>
                                                {{ $item['date']->format(abs($item['date']->diffInDays($today)) < 300 ? 'M j' : 'M j, Y') }}
                                            </time>
                                        @else
                                            <span class="font-medium text-gray-700 whitespace-nowrap tabular-nums dark:text-gray-200">{!! $item['html'] !!}</span>
                                        @endif
                                    @endforeach

                                </div>
                            @endif
                        </a>
                    @empty
                        <div class="aura-board-empty" data-kanban-dropzone="{{ $columnKey }}">
                            {{ __('No :resource', ['resource' => \Illuminate\Support\Str::lower(__($model->pluralName()))]) }}
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    {{-- More columns to the right: a short fade on the page background, not a scrollbar-only hint. --}}
    <div x-show="more" x-cloak x-transition.opacity class="hidden absolute inset-y-0 right-0 w-12 bg-gradient-to-l from-white to-transparent pointer-events-none sm:block dark:from-gray-900" aria-hidden="true"></div>
</div>
