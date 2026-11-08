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
        $sections = \Aura\Base\RecordLayout\RecordViewSections::split($this->viewFields, $model);
        $cards = (! $inModal && $sections['related'] === []) ? \Aura\Base\RecordLayout\RecordViewSections::cards($this->viewFields, $model) : [];
        $splitRelated = ! $inModal && ($splitRelated ?? true) && ($sections['related'] !== [] || $cards !== []);

        // Fields the header facts strip or a relation card already shows are not repeated in Details.
        // Try header facts plus card relations first, then header facts alone, so the details column never ends up empty.
        if (! $inModal) {
            $headerSlugs = array_column(\Aura\Base\RecordLayout\RecordViewSections::header($this->viewFields, $model), 'slug');
            $cardSlugs = array_map(fn ($card) => $card['field']['slug'], $cards);

            foreach (array_unique([array_merge($headerSlugs, $cardSlugs), $headerSlugs], SORT_REGULAR) as $shownElsewhere) {
                if ($shownElsewhere === []) {
                    continue;
                }

                $trimmed = \Aura\Base\RecordLayout\RecordViewSections::split($this->viewFields, $model, $shownElsewhere);

                if ($trimmed['details'] !== []) {
                    $sections = $trimmed;
                    break;
                }
            }
        }

        // Values outside any panel form one "Details" section ahead of the panels.
        $loose = array_values(array_filter($sections['details'], fn ($field) => ! (($field['field'] ?? null) instanceof \Aura\Base\Fields\Panel)));
        $panels = array_values(array_filter($sections['details'], fn ($field) => ($field['field'] ?? null) instanceof \Aura\Base\Fields\Panel));
    @endphp

    <div class="col-span-1 mx-0 min-w-0 sm:col-span-3 {{ $splitRelated ? 'aura-record-columns xl:grid xl:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] xl:gap-10 xl:items-start 2xl:grid-cols-[minmax(0,24rem)_minmax(0,1fr)] 2xl:gap-12' : '' }}">
        @if ($sections['details'] !== [])
        {{-- Sections bring px-2; -mx-2 puts their titles flush with the page title. --}}
        <div class="-mx-2 min-w-0 {{ $splitRelated ? 'aura-record-details' : '' }}" wire:key="resource-view-fields" data-record-details>
            @if ($loose !== [])
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

            @foreach($panels as $key => $field)
            <x-aura::fields.conditions :field="$field" :model="$model" wire:key="resource-field-{{ $field['slug'] ?? $key }}">
                <x-dynamic-component :component="$field['field']->view()" :field="$field" :form="$form" :mode="$mode" />
            </x-aura::fields.conditions>
            @endforeach
        </div>
        @endif

        @if ($sections['related'] !== [] || $cards !== [])
        {{-- Related records as unboxed lists; the has-many view brings its own px-4, which -mx-4 cancels so the lists align with the column. --}}
        <div class="space-y-8 min-w-0 {{ $splitRelated ? '' : 'mt-2' }}" wire:key="resource-view-related" data-record-related>
            @if ($cards !== [])
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" data-record-cards>
                    @foreach ($cards as $card)
                        <x-aura::resource.record-card :record="$card['record']" :label="__($card['field']['name'] ?? $card['record']->singularName())" :facts="$card['facts']" wire:key="resource-card-{{ $card['field']['slug'] }}" />
                    @endforeach
                </div>
            @endif

            @foreach($sections['related'] as $key => $field)
            <x-aura::fields.conditions :field="$field" :model="$model" wire:key="resource-related-{{ $field['slug'] ?? $key }}">
                <div class="-mx-4">
                    <x-dynamic-component :component="$field['field']->view()" :field="$field" :form="$form" :mode="$mode" />
                </div>
            </x-aura::fields.conditions>
            @endforeach
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
