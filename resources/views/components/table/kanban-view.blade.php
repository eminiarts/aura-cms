{{-- resources/views/components/table/kanban-view.blade.php --}}
@php
    $kanban = $kanban ?? $this->resolvedKanbanConfiguration();
    $groupField = $kanban['group_field'] ?? 'status';
    $cardTitle = $kanban['card_title'] ?? 'title';
    $cardSubtitle = $kanban['card_subtitle'] ?? null;
    $showEmptyColumns = $kanban['show_empty_columns'] ?? true;
    $items = method_exists($rows, 'items') ? collect($rows->items()) : collect($rows);
@endphp

<div class="kanban-board flex gap-4 overflow-x-auto p-4" wire:key="kanban-board-{{ $model->getType() }}">
    @foreach ($this->kanbanStatuses as $columnKey => $column)
        @php
            $columnRows = $items->filter(
                fn ($row) => (string) data_get($row, $groupField) === (string) $columnKey
            );
        @endphp

        @continue(! ($column['visible'] ?? true) || (! $showEmptyColumns && $columnRows->isEmpty()))

        <section class="kanban-column w-80 flex-shrink-0 rounded-xl bg-gray-950/[0.04] p-4 dark:bg-white/5"
            data-kanban-column="{{ $columnKey }}"
            wire:key="kanban-column-{{ $model->getType() }}-{{ $columnKey }}">
            <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
                @if (! empty($column['color']))
                    <span class="size-2 rounded-full {{ $column['color'] }}" aria-hidden="true"></span>
                @endif
                <span>{{ __($column['value'] ?? $columnKey) }}</span>
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ $columnRows->count() }}</span>
            </h3>

            <div class="min-h-12 space-y-3">
                @if ($columnRows->isEmpty())
                    <div class="h-12" data-kanban-dropzone="{{ $columnKey }}"></div>
                @endif

                @foreach ($columnRows as $row)
                    <article class="rounded-lg bg-white p-4 shadow-xs ring-1 ring-gray-950/10 dark:bg-gray-800 dark:ring-white/10"
                        data-kanban-card="{{ $row->getKey() }}"
                        wire:key="kanban-card-{{ $model->getType() }}-{{ $row->getKey() }}">
                        <div class="min-w-0">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                @if (method_exists($row, 'display'))
                                    {!! $row->display($cardTitle) !!}
                                @else
                                    {{ data_get($row, $cardTitle) }}
                                @endif
                            </h4>
                            @if ($cardSubtitle)
                                <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    @if (method_exists($row, 'display'))
                                        {!! $row->display($cardSubtitle) !!}
                                    @else
                                        {{ data_get($row, $cardSubtitle) }}
                                    @endif
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
