@if ($this->settings['header_before'])
    {{ app('aura')::injectView('header_before') }}
@endif

@if ($this->settings['header'])
    @if (View::exists($view = 'aura.' . Str::lower($model->getType()) . '.header'))
        @include($view)
    @elseif(View::exists('aura::' . $view))
        @include('aura::' . $view)
    @else
        @php
            // A table embedded in a record (HasMany etc.) is a section of that
            // page: section title, row count, secondary create button. The
            // resource index is the page itself: page title, primary create.
            $isRelationTable = (bool) optional(optional($this)->field)['name'];
            // Record count (after search and filters) beside the title, on the page and on relation sections alike.
            $rowCount = isset($rows) && method_exists($rows, 'total') ? $rows->total() : null;
            // The create button stays in the title row on an empty list too; the empty block only words it.
            $createLabel = __('New :resource', ['resource' => Str::lower(__($model->singularName()))]);
            // A page's description goes under the title row, so the title sits at the height of every other page title.
            $indexDescription = ! $isRelationTable && $this->settings['title'] && method_exists($model, 'indexDescription') ? $model->indexDescription() : null;
        @endphp
        {{-- A relation section's row keeps the 32px button height without a button too, so its title sits at the same height. --}}
        {{-- The page's create button keeps its object ("New company") while it fits beside the title and reads
             "+ New" only when the full label would wrap the title (table/create-button). --}}
        <div class="flex gap-3 justify-between items-center {{ $isRelationTable ? 'mt-2 mb-3 min-h-8' : 'mt-1 min-h-9 sm:mt-8 sm:min-h-[3.375rem] sm:items-end'.(filled($indexDescription) ? '' : ' sm:mb-8') }}" @if ($isRelationTable) data-relation-header @endif>

            @if ($this->settings['title'])
                <div class="min-w-0">
                    @if ($isRelationTable)
                        <h2 class="aura-section-title">
                            {{ __($this->field['name']) }}
                            @if ($rowCount !== null)
                                <span class="aura-section-count">{{ number_format($rowCount) }}</span>
                            @endif
                        </h2>
                    @else
                        <h1 class="aura-page-title">
                            {{ __($model->pluralName()) }}
                            @if ($rowCount !== null && ! ($collectionIsEmpty ?? false))
                                <span class="aura-page-count">{{ number_format($rowCount) }}</span>
                            @endif
                        </h1>
                    @endif


                    @if (optional(optional($this)->field)['description'])
                        <span class="aura-meta">{{ __($this->field['description']) }}</span>
                    @endif
                </div>
            @endif


            @can('create', $model)
                @if ($this->settings['create'])
                    {{-- A page's create button takes the width the title leaves (it decides there whether its full label fits). --}}
                    <div class="create-resource {{ $isRelationTable ? 'shrink-0' : 'flex-1' }}">
                        <div>
                            @if ($this->settings['create_in_modal'])
                                <a href="#" @class(['block' => ! $isRelationTable])
                                    wire:click.prevent="$dispatch('openModal', { component: 'aura::resource-create-modal', arguments: {
                                type: '{{ $this->model->getType() }}',
                                params: {
                                    'for': '{{ $this->field['relation'] ?? $parent->getType() }}',
                                    'id': '{{ $parent->id }}'
                                }
                            }})">
                                    @if ($isRelationTable)
                                        <x-aura::button.border size="sm">
                                            <x-slot:icon>
                                                <x-aura::icon icon="plus" />
                                            </x-slot>
                                            <span>{{ $createLabel }}</span>
                                        </x-aura::button.border>
                                    @else
                                        <x-aura::table.create-button :label="$createLabel" />
                                    @endif
                                </a>
                            @else
                                @if($this->createLink)
                                    @if ($isRelationTable)
                                        <x-aura::button.border size="sm" href="{{ $this->createLink }}">
                                            <x-slot:icon>
                                                <x-aura::icon icon="plus" />
                                            </x-slot>
                                            <span>{{ $createLabel }}</span>
                                        </x-aura::button.border>
                                    @else
                                        <x-aura::table.create-button :href="$this->createLink" :label="$createLabel" />
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
            @endcan

        </div>

        @if (filled($indexDescription))
            <p class="mt-1 text-sm text-gray-500 sm:mb-8 dark:text-gray-400" data-page-description>{{ $indexDescription }}</p>
        @endif
    @endif
@endif
@if ($this->settings['header_after'])
    {{ app('aura')::injectView('header_after') }}
@endif
