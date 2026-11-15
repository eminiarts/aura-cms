@php
    // Key fields promoted under the title: a number large and tabular, a status as its tag.
    $facts = \Aura\Base\RecordLayout\RecordViewSections::header($this->viewFields, $model);
    // Computed facts (a company's open pipeline) from the resource's optional recordHighlights(): each
    // ['label' => …, 'value' => string or Htmlable], after the field facts, three facts in all.
    $highlights = method_exists($model, 'recordHighlights') ? array_values(array_filter((array) $model->recordHighlights(), fn ($item) => is_array($item) && isset($item['label']))) : [];
    $highlights = array_slice($highlights, 0, max(0, 3 - count($facts)));
    $recordTemplate ??= false;
    $canEdit = Route::has('aura.' . $slug . '.edit') && auth()->user()?->can('update', $model);
    // The actions menu sits in the title row (a 36px "more" button beside the pencil on phones, as on the
    // edit header); a resource that shows its actions as buttons keeps them in the action row.
    $actionButtons = count($this->actions) && $this->model::$showActionsAsButtons;
    $actionsMenu = count($this->actions) && ! $actionButtons;
    // Without other actions the phone action row would only hold Edit, which moves into the title row.
    $hasPhoneActions = $recordLayout->panels(\Aura\Base\RecordLayout\RecordLayoutRegion::HeaderActions) !== [] || $actionButtons;
@endphp

{{-- The same spacing as the edit and create headers, so avatar, eyebrow and title stay put when switching. --}}
<div class="mt-1 mb-6 sm:my-8 aura-record-block" data-record-header>
    {{-- Actions sit to the right of the title from sm, as on list pages; on phones the header row
         dissolves into the block, Edit becomes an icon button in the title row, and any other actions
         sort last, right-aligned under the facts strip. --}}
    <div class="aura-record-header {{ $recordLayout->hasPanels() ? 'sm:flex-wrap' : '' }}">
        <div class="flex flex-1 gap-3 items-center min-w-0 sm:min-w-[10rem]">
            @yield('view-header')
            {{-- The record is the subject of the page: its avatar, resource name as eyebrow, record title as H1. --}}
            @if ($model->avatarShape())
                <x-aura::avatar :record="$model" size="lg" />
            @elseif ($recordTemplate && filled($model->getIcon()))
                {{-- The record template keeps the 40px tile for records without an avatar: the resource icon. --}}
                <span class="aura-record-icon-tile" aria-hidden="true">{!! $model->getIcon() !!}</span>
            @endif
            <div class="min-w-0">
                <p class="aura-eyebrow">{{ __($model->singularName()) }}</p>
                <h1 class="mt-0.5 break-words aura-page-title line-clamp-2 sm:line-clamp-1" title="{{ $model->title() }}">{{ $model->title() }}</h1>
            </div>

            @if ($actionsMenu)
                <div class="ml-auto shrink-0 sm:-mr-2 aura-record-title-action">
                    @include('aura::livewire.resource.actions', ['iconTriggerOnPhones' => true])
                </div>
            @endif

            {{-- Phones: Edit is a 36px pencil in the title row, so the header has no action band of its own. --}}
            @if ($canEdit)
                <x-aura::button.border size="icon" href="{{ route('aura.' . $slug . '.edit', $model->id) }}"
                    class="shrink-0 sm:hidden aura-record-title-action {{ $actionsMenu ? '' : 'ml-auto' }}" aria-label="{{ __('Edit') }}"
                    x-data x-init="tippy($el, { content: @js(__('Edit')), arrow: false, theme: 'aura', placement: 'bottom', offset: [0, 8] })">
                    <x-aura::icon.edit class="size-4" aria-hidden="true" />
                </x-aura::button.border>
            @endif
        </div>

        <div @class([
            'aura-record-header-actions',
            'flex-wrap' => $recordLayout->hasPanels(),
            'aura-record-header-actions-sm' => ! $hasPhoneActions,
        ])>
            @if ($recordLayout->panels(\Aura\Base\RecordLayout\RecordLayoutRegion::HeaderActions) !== [])
                <div class="flex flex-wrap items-center gap-2" data-record-layout-region="header-actions">
                    @foreach ($recordLayout->panels(\Aura\Base\RecordLayout\RecordLayoutRegion::HeaderActions) as $registeredPanel)
                        @include('aura::livewire.resource.record-layout.panel', ['registeredPanel' => $registeredPanel])
                    @endforeach
                </div>
            @endif

            @if ($actionButtons)
                @include('aura::livewire.resource.actions')
            @endif

            {{-- A reading page has no primary action: Edit is a secondary button with its pencil. --}}
            @if ($canEdit)
                    <x-aura::button.border href="{{ route('aura.' . $slug . '.edit', $model->id) }}" class="hidden sm:inline-flex">
                        <x-slot:icon>
                            <x-aura::icon icon="edit" />
                        </x-slot>
                        {{ __('Edit') }}
                    </x-aura::button.border>
            @endif
        </div>
    </div>

    @if ($facts !== [] || $highlights !== [])
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
                        <span class="aura-record-fact-number">{!! \Aura\Base\Fields\Number::unitMarkup($fact, $value) !!}</span>
                    @else
                        <span @class(['aura-record-fact-value', 'aura-record-fact-text' => $recordTemplate && ! ($instance instanceof \Aura\Base\Fields\BelongsTo)])>{!! $value !!}</span>
                    @endif
                </div>
            @endforeach
            @foreach ($highlights as $highlight)
                <div class="aura-record-fact" data-record-highlight>
                    <span class="aura-eyebrow">{{ $highlight['label'] }}</span>
                    <span class="aura-record-fact-number">{{ $highlight['value'] ?? '–' }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
