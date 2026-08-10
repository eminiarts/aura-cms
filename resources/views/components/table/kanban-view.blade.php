<div class="kanban-board flex gap-4 overflow-x-auto p-4" wire:key="kanban-board-{{ $model->getType() }}">
    @foreach ($this->kanbanStatuses as $columnKey => $column)
        @php
            $columnRows = collect($rows->items())->filter(
                fn ($row) => (string) $row->{$kanban['group_field']} === (string) $columnKey
            );
        @endphp

        @continue(! $column['visible'] || (! $kanban['show_empty_columns'] && $columnRows->isEmpty()))

        <section class="kanban-column w-80 flex-shrink-0 rounded-xl bg-gray-950/[0.04] p-4 dark:bg-white/5"
            data-kanban-column="{{ $columnKey }}"
            wire:key="kanban-column-{{ $model->getType() }}-{{ $columnKey }}">
            <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
                @if ($column['color'])
                    <span class="size-2 rounded-full {{ $column['color'] }}" aria-hidden="true"></span>
                @endif
                <span>{{ __($column['value']) }}</span>
                <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ $columnRows->count() }}</span>
            </h3>

            <div class="min-h-12 space-y-3"
                wire:sort="moveKanbanCard"
                wire:sort:group="{{ $model->getType() }}-kanban-cards"
                wire:sort:group-id="{{ $columnKey }}">
                @if ($columnRows->isEmpty())
                    <div class="h-12" data-kanban-dropzone="{{ $columnKey }}"></div>
                @endif

                @foreach ($columnRows as $row)
                    <article class="rounded-lg bg-white p-4 shadow-xs ring-1 ring-gray-950/10 dark:bg-gray-800 dark:ring-white/10"
                        data-kanban-card="{{ $row->getKey() }}"
                        wire:key="kanban-card-{{ $model->getType() }}-{{ $row->getKey() }}"
                        wire:sort:item="{{ $row->getKey() }}">
                        <div class="flex items-start gap-2">
                            <button type="button" class="mt-0.5 cursor-grab text-gray-400"
                                data-kanban-drag-handle="{{ $row->getKey() }}" wire:sort:handle>
                                <span class="sr-only">{{ __('Move card') }}</span>
                                <x-aura::icon icon="move" size="sm" />
                            </button>
                            <div class="min-w-0">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {!! $row->display($kanban['card_title']) !!}
                                </h4>
                                @if ($kanban['card_subtitle'])
                                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {!! $row->display($kanban['card_subtitle']) !!}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
