@php
    // Key fields promoted under the title: a number large and tabular, a status as its tag.
    $facts = \Aura\Base\RecordLayout\RecordViewSections::header($this->viewFields, $model);
@endphp

<div class="mt-4 mb-6 sm:mt-6 sm:mb-8 aura-record-block" data-record-header>
    {{-- Actions sit to the right of the title from sm, as on list pages; on phones the header row
         dissolves into the block and the actions sort last, right-aligned under the facts strip. --}}
    <div class="aura-record-header {{ $recordLayout->hasPanels() ? 'sm:flex-wrap' : '' }}">
        <div class="flex flex-1 gap-3 items-center min-w-0 sm:min-w-[10rem]">
            @yield('view-header')
            {{-- The record is the subject of the page: its avatar, resource name as eyebrow, record title as H1. --}}
            @if ($model->avatarShape())
                <x-aura::avatar :record="$model" size="lg" />
            @endif
            <div class="min-w-0">
                <p class="aura-eyebrow">{{ __($model->singularName()) }}</p>
                <h1 class="mt-0.5 break-words aura-page-title line-clamp-2 sm:line-clamp-1" title="{{ $model->title() }}">{{ $model->title() }}</h1>
            </div>
        </div>

        <div class="aura-record-header-actions {{ $recordLayout->hasPanels() ? 'flex-wrap' : '' }}">
            @if ($recordLayout->panels(\Aura\Base\RecordLayout\RecordLayoutRegion::HeaderActions) !== [])
                <div class="flex flex-wrap items-center gap-2" data-record-layout-region="header-actions">
                    @foreach ($recordLayout->panels(\Aura\Base\RecordLayout\RecordLayoutRegion::HeaderActions) as $registeredPanel)
                        @include('aura::livewire.resource.record-layout.panel', ['registeredPanel' => $registeredPanel])
                    @endforeach
                </div>
            @endif

            @include('aura::livewire.resource.actions')

            {{-- A reading page has no primary action: Edit is a secondary button with its pencil. --}}
            @can('update', $model)
                @if(Route::has('aura.' . $slug . '.edit'))
                    <x-aura::button.border href="{{ route('aura.' . $slug . '.edit', $model->id) }}">
                        <x-slot:icon>
                            <x-aura::icon icon="edit" />
                        </x-slot>
                        {{ __('Edit') }}
                    </x-aura::button.border>
                @endif
            @endcan
        </div>
    </div>

    @if ($facts !== [])
        <div class="aura-record-facts sm:mt-5" data-record-facts>
            @foreach ($facts as $fact)
                @php
                    $instance = $fact['field'] ?? null;
                    $statusOption = $instance instanceof \Aura\Base\Fields\Status
                        ? collect($fact['options'] ?? [])->firstWhere('key', $model->{$fact['slug']})
                        : null;
                    $value = $statusOption ? null : $model->display($fact['slug']);
                    $isEmpty = ! $statusOption && ($value === null || $value === '' || (is_string($value) && trim($value) === ''));
                @endphp
                <div class="aura-record-fact" data-record-fact="{{ $fact['slug'] }}">
                    <span class="aura-eyebrow">{{ __($fact['name'] ?? $fact['slug']) }}</span>
                    @if ($statusOption)
                        <span class="aura-record-fact-value">
                            <span class="aura-tag aura-tag-status {{ $statusOption['color'] ?? '' }}">
                                <svg class="aura-tag-dot" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3" /></svg>
                                {{ __($statusOption['value']) }}
                            </span>
                        </span>
                    @elseif ($isEmpty)
                        <span class="aura-record-fact-value text-gray-400 dark:text-gray-500">–</span>
                    @elseif ($instance instanceof \Aura\Base\Fields\Number)
                        <span class="aura-record-fact-number">{!! $value !!}</span>
                    @else
                        <span class="aura-record-fact-value">{!! $value !!}</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
