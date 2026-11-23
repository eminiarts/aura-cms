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
            $createLabel = __('New :resource', ['resource' => Str::lower(__($model->singularName()))]);
            // A page list without any records shows its create button in the empty state (list-view), not twice.
            $createInEmptyState = ! $isRelationTable && ($collectionIsEmpty ?? false) && ($currentView ?? null) === 'list'
                && ! $this->settings['create_in_modal'] && $this->createLink;
        @endphp
        {{-- A relation section's row keeps the 32px button height without a button too, so its title sits at the same height. --}}
        <div class="flex justify-between items-center {{ $isRelationTable ? 'mt-2 mb-3 min-h-8' : 'mt-1 min-h-9 sm:mt-6' }}" @if ($isRelationTable) data-relation-header @endif>

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


            {{-- No records yet: no toolbar, so "Edit resource" sits on the title row. --}}
            @if (! $isRelationTable && ($collectionIsEmpty ?? false))
                @include('aura::components.table.resource-editor-button')
            @endif

            @can('create', $model)
                @if ($this->settings['create'] && ! $createInEmptyState)
                    <div class="create-resource">
                        <div>
                            @if ($this->settings['create_in_modal'])
                                <a href="#"
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
                                        <x-aura::button>
                                            <x-slot:icon>
                                                <x-aura::icon icon="plus" />
                                            </x-slot>
                                            <span>{{ $createLabel }}</span>
                                        </x-aura::button>
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
                                        <x-aura::button href="{{ $this->createLink }}">
                                            <x-slot:icon>
                                                <x-aura::icon icon="plus" />
                                            </x-slot>
                                            <span>{{ $createLabel }}</span>
                                        </x-aura::button>
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
            @endcan

        </div>
    @endif
@endif
@if ($this->settings['header_after'])
    {{ app('aura')::injectView('header_after') }}
@endif
