@if($model::usesTitle())
<div class="mb-4">
    <x-aura::fields.wrapper :field="['slug' => 'title']" wrapperClass="" class="-mx-4">
        <x-aura::input.text label="Title" wire:model="post.title" error="post.title" placeholder="Title">
        </x-aura::input.text>
    </x-aura::fields.wrapper>
</div>
@endif

<div class="grid grid-cols-1 gap-6 mt-4 aura-view-post-container sm:grid-cols-3" x-data="{
     init() {
        const container = document.querySelector('.aura-view-post-container');
        const inputs = container.querySelectorAll('input, select, textarea, .aura-input');

        inputs.forEach((input) => {
            if (!input.hasAttribute('readonly')) {
               // input.setAttribute('readonly', true);
            }
        });
    }
}">

    @php
        // Record page: details column on the left, related records on the right from xl up (below xl,
        // next to the app sidebar, embedded lists would be too narrow for their columns). Related
        // records are the embedded lists; a record without lists shows its BelongsTo relations as
        // cards instead. Modals and pages with record-layout side panels keep the single column.
        // A record with nothing beside its values renders in its form's frame instead (view.blade.php
        // decides it with RecordViewSections::formFrame()), so none of this applies to it.
        $formFrame ??= null;
        $sections = $formFrame !== null ? ['details' => [], 'related' => []] : \Aura\Base\RecordLayout\RecordViewSections::split($this->viewFields, $model);

        // The record template (RecordViewSections::template(), decided in view.blade.php): a narrow panel (style
        // width up to 33%, the form's sidebar card) becomes the "Properties" rail beside a main column of body,
        // related lists and activity, so the page mirrors the form. Its relations stay inline values there
        // instead of turning into cards.
        $isRailPanel = fn ($field) => \Aura\Base\RecordLayout\RecordViewSections::isRailPanel($field);
        $useRail = ! $inModal && ($splitRelated ?? true) && $formFrame === null && ($recordTemplate ?? false);
        // A record with a history (RecordsActivity) shows it after the related lists.
        $showActivity = ! $inModal && $formFrame === null && \Aura\Base\RecordLayout\RecordViewSections::recordsActivity($model);

        $cards = ($formFrame === null && ! $inModal && ! $useRail && $sections['related'] === []) ? \Aura\Base\RecordLayout\RecordViewSections::cards($this->viewFields, $model) : [];
        // Fields the header facts strip or a relation card already shows are not repeated in Details.
        // When nothing else is left, the page renders header and related records without a details column.
        if (! $inModal && $formFrame === null) {
            $shownElsewhere = array_merge(
                \Aura\Base\RecordLayout\RecordViewSections::headerFactSlugs($this->viewFields, $model),
                array_map(fn ($card) => $card['field']['slug'], $cards),
            );

            if ($shownElsewhere !== []) {
                $sections = \Aura\Base\RecordLayout\RecordViewSections::split($this->viewFields, $model, $shownElsewhere);
            }
        }

        $splitRelated = ! $inModal && ! $useRail && ($splitRelated ?? true) && $sections['details'] !== [] && ($sections['related'] !== [] || $cards !== []);

        // Values outside any panel form one "Details" section ahead of the panels.
        $loose = array_values(array_filter($sections['details'], fn ($field) => ! (($field['field'] ?? null) instanceof \Aura\Base\Fields\Panel)));
        $panels = array_values(array_filter($sections['details'], fn ($field) => ($field['field'] ?? null) instanceof \Aura\Base\Fields\Panel));
        $railPanels = $useRail ? array_values(array_filter($panels, $isRailPanel)) : [];
        $panels = $useRail ? array_values(array_filter($panels, fn ($field) => ! $isRailPanel($field))) : $panels;

        // Beside a rail, a main column that holds nothing but one long text (a task's description, a deal's
        // notes) reads as the record's body: the text under the header, without a "Details" heading or a label.
        // Empty, it is a quiet "Add …" link to the form.
        $mainFields = array_merge($loose, ...array_map(fn ($panel) => $panel['fields'] ?? [], $panels));
        $bodyField = ($useRail && count($mainFields) === 1
            && (($mainFields[0]['field'] ?? null) instanceof \Aura\Base\Fields\Textarea || ($mainFields[0]['field'] ?? null) instanceof \Aura\Base\Fields\Wysiwyg))
            ? $mainFields[0] : null;
        $bodyValue = $bodyField ? $model->display($bodyField['slug']) : null;
        $bodyEmpty = $bodyField && blank(trim(strip_tags((string) $bodyValue)));
        $editUrl = Route::has('aura.' . $model->getSlug() . '.edit') && auth()->user()?->can('update', $model)
            ? route('aura.' . $model->getSlug() . '.edit', $model->id) : null;
    @endphp

    <div class="col-span-1 mx-0 min-w-0 sm:col-span-3 {{ $splitRelated ? 'aura-record-columns xl:grid xl:grid-cols-[minmax(0,25rem)_minmax(0,1fr)] xl:gap-10 xl:items-start 2xl:grid-cols-[minmax(0,26rem)_minmax(0,1fr)] 2xl:gap-12' : '' }} {{ $useRail ? 'aura-record-rail-layout lg:grid lg:grid-cols-[minmax(0,1fr)_20rem] lg:grid-rows-[auto_1fr] lg:gap-x-12 lg:items-start' : '' }}">
        @if ($formFrame !== null)
        {{-- The form's frame (edit.blade.php) with view components: the same tab bar, a panel is the form's
             card (frame="form" reaches fields/panel and fields/wrapper through @aware), plain values get the
             card a panel would give them, and .aura-view-grid lays the values out at their form widths. --}}
        <div class="flex flex-wrap items-start -mx-2 min-w-0 aura-view-grid" wire:key="resource-view-fields" data-record-details data-record-frame="form">
            @if ($formFrame['card'] && ! $inModal)
                <div class="px-2 w-full" data-form-card>
                    <div class="mb-8 aura-card">
                        <div class="mb-2">
                            <div class="flex flex-wrap items-start -mx-2 w-[calc(100%+1rem)]">
                                @foreach ($formFrame['fields'] as $key => $field)
                                <x-aura::fields.conditions :field="$field" :model="$model" wire:key="resource-field-{{ $field['slug'] ?? $key }}">
                                    <x-dynamic-component :component="$field['field']->view()" :field="$field" :form="$form" :mode="$mode" frame="form" />
                                </x-aura::fields.conditions>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @else
                @foreach ($formFrame['fields'] as $key => $field)
                <x-aura::fields.conditions :field="$field" :model="$model" wire:key="resource-field-{{ $field['slug'] ?? $key }}">
                    <x-dynamic-component :component="$field['field']->view()" :field="$field" :form="$form" :mode="$mode" frame="form" />
                </x-aura::fields.conditions>
                @endforeach
            @endif
        </div>

        @unless ($inModal)
            @include('aura::livewire.resource.record-meta', ['attributes' => new \Illuminate\View\ComponentAttributeBag()])
        @endunless
        @elseif ($sections['details'] !== [])
        {{-- Sections bring px-2; -mx-2 puts their titles flush with the page title. --}}
        <div class="-mx-2 min-w-0 {{ $splitRelated ? 'aura-record-details' : '' }}" wire:key="resource-view-fields" data-record-details>
            @if ($bodyField)
                <x-aura::fields.conditions :field="$bodyField" :model="$model" wire:key="resource-field-{{ $bodyField['slug'] }}">
                    {{-- Below lg the rail follows the body, so the body closes with a section gap. The text fills the
                         main column up to 720px (about 90 characters), no dead gap before the rail. --}}
                    @if ($bodyEmpty)
                        @if ($editUrl)
                            <div class="px-2 mb-8 lg:mb-0" data-record-body="empty">
                                <a href="{{ $editUrl }}" class="aura-record-body-add">
                                    <x-aura::icon.plus class="size-4" aria-hidden="true" />
                                    {{ \Illuminate\Support\Str::lower($bodyField['name'] ?? '') === 'description' ? __('Add a description') : __('Add :field', ['field' => \Illuminate\Support\Str::lower(__($bodyField['name'] ?? ''))]) }}
                                </a>
                            </div>
                        @endif
                    @elseif ($bodyField['field'] instanceof \Aura\Base\Fields\Wysiwyg)
                        <div class="px-2 mb-8 max-w-[45rem] prose prose-sm text-gray-800 lg:mb-0 dark:prose-invert dark:text-gray-200" data-record-body>{!! $bodyValue !!}</div>
                    @else
                        {{-- One line on purpose: whitespace-pre-line would render the template's own line breaks. --}}
                        <div class="px-2 mb-8 max-w-[45rem] text-[15px] leading-relaxed text-gray-800 whitespace-pre-line break-words lg:mb-0 dark:text-gray-200" data-record-body>{!! trim((string) $bodyValue) !!}</div>
                    @endif
                </x-aura::fields.conditions>
            @elseif ($loose !== [])
                <div class="px-2 aura-record-section" data-record-section="details">
                    <h2 class="aura-record-section-title">{{ __('Details') }}</h2>
                    <div class="flex flex-wrap items-start -mx-2 aura-record-section-body">
                        @foreach($loose as $key => $field)
                        <x-aura::fields.conditions :field="$field" :model="$model" wire:key="resource-field-{{ $field['slug'] ?? $key }}">
                            <x-dynamic-component :component="$field['field']->view()" :field="$field" :form="$form" :mode="$mode" />
                        </x-aura::fields.conditions>
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach($bodyField ? [] : $panels as $key => $field)
            <x-aura::fields.conditions :field="$field" :model="$model" wire:key="resource-field-{{ $field['slug'] ?? $key }}">
                <x-dynamic-component :component="$field['field']->view()" :field="$field" :form="$form" :mode="$mode" />
            </x-aura::fields.conditions>
            @endforeach

            @unless ($inModal || $useRail)
                {{-- Beside the related records (xl) the meta closes the details column; stacked below xl it
                     moves after the related lists (the copy at their end), so it still closes the page. --}}
                @include('aura::livewire.resource.record-meta', ['attributes' => new \Illuminate\View\ComponentAttributeBag(['class' => $splitRelated ? 'px-2 max-xl:!hidden' : 'px-2'])])
            @endunless
        </div>
        @endif

        @if ($useRail)
        {{-- Right rail: the narrow panels, then the meta line. On phones the rail follows the body and the meta
             moves to the end of the page (the copy after the activity). --}}
        <aside class="-mx-2 min-w-0 aura-record-rail lg:row-span-2 lg:col-start-2 lg:row-start-1" wire:key="resource-view-rail" data-record-rail>
            @foreach($railPanels as $key => $field)
            <x-aura::fields.conditions :field="$field" :model="$model" wire:key="resource-field-{{ $field['slug'] ?? $key }}">
                <x-dynamic-component :component="$field['field']->view()" :field="$field" :form="$form" :mode="$mode" />
            </x-aura::fields.conditions>
            @endforeach

            @include('aura::livewire.resource.record-meta', ['attributes' => new \Illuminate\View\ComponentAttributeBag(['class' => 'px-2 max-lg:!hidden aura-record-meta-stacked'])])
        </aside>
        @endif

        @if ($sections['related'] !== [] || $cards !== [] || $showActivity || $useRail)
        {{-- Related records as unboxed lists; the has-many view brings its own px-2, which -mx-2 cancels so the lists align with the column. --}}
        {{-- Stacked under the details column (below xl) the related records keep a section gap from the meta line. --}}
        <div class="space-y-8 min-w-0 {{ $splitRelated ? 'mt-6 xl:mt-0' : 'mt-2' }} {{ $useRail ? 'lg:col-start-1 lg:mt-8' : '' }}" wire:key="resource-view-related" data-record-related>
            @if ($cards !== [])
                {{-- Cards in one row share its height (grid stretch). --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" data-record-cards>
                    @foreach ($cards as $card)
                        <x-aura::resource.record-card :record="$card['record']" :label="__($card['field']['name'] ?? $card['record']->singularName())" :facts="$card['facts']" wire:key="resource-card-{{ $card['field']['slug'] }}" />
                    @endforeach
                </div>
            @endif

            @foreach($sections['related'] as $key => $field)
            <x-aura::fields.conditions :field="$field" :model="$model" wire:key="resource-related-{{ $field['slug'] ?? $key }}">
                <div class="-mx-2">
                    <x-dynamic-component :component="$field['field']->view()" :field="$field" :form="$form" :mode="$mode" />
                </div>
            </x-aura::fields.conditions>
            @endforeach

            @if ($showActivity)
                <x-aura::resource.activity :model="$model" wire:key="resource-view-activity" />
            @endif

            {{-- Without a details column the meta line closes the related records instead, and so it does
                 below xl, where the related records stack under the details column, and on phones beside a rail. --}}
            @if (! $inModal && $useRail)
                @include('aura::livewire.resource.record-meta', ['attributes' => new \Illuminate\View\ComponentAttributeBag(['class' => '!mt-6 lg:!hidden aura-record-meta-stacked'])])
            @elseif (! $inModal && $sections['details'] === [])
                @include('aura::livewire.resource.record-meta', ['attributes' => new \Illuminate\View\ComponentAttributeBag(['class' => '!mt-6'])])
            @elseif (! $inModal && $splitRelated)
                @include('aura::livewire.resource.record-meta', ['attributes' => new \Illuminate\View\ComponentAttributeBag(['class' => '!mt-6 xl:!hidden'])])
            @endif
        </div>
        @endif

        <div>
            @if (count($errors->all()))
        <div class="block">
            <div class="mt-8 form_errors">
                <strong class="block text-red-600">Unfortunately, there were still the following validation
                    errors:</strong>
                <div class="text-red-600 prose">
                    <ul>
                        @foreach ($errors->all() as $message)
                        <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif
        </div>

    </div>

</div>
