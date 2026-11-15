{{-- The fields of a resource form (edit and create share it): with a narrow panel (style width up to 33%,
     the sidebar card) beside other fields, a main column and a 20rem right rail from lg with a 40px gap, the
     record view's grid, so create, edit and view read as one page; plain inputs without a panel get the card a
     panel would give them; anything else flows in the field grid. Expects $formSections
     (RecordFormSections::arrange()), $model, $form, $mode and $inModal. --}}
@php
    $isRailPanel = fn ($field) => \Aura\Base\RecordLayout\RecordViewSections::isRailPanel($field);
    $useRail = ! $inModal && $formSections['rail'];
@endphp

<div class="col-span-1 mx-0 min-w-0 sm:col-span-3 {{ $useRail ? 'aura-form-rail-layout lg:grid lg:grid-cols-[minmax(0,1fr)_20rem] lg:grid-rows-[auto_1fr] lg:gap-x-10 lg:items-start' : '' }}">
    @if ($useRail)
        @php
            // The main column's first panel leads; further panels (a deal's notes) follow it from lg, and on phones,
            // where the columns stack, they come after the rail, so the required properties are not below them.
            $mainFields = array_filter($formSections['fields'], fn ($field) => ! $isRailPanel($field));
            $leadKey = array_key_first($mainFields);
            $restFields = array_filter($mainFields, fn ($key) => $key !== $leadKey, ARRAY_FILTER_USE_KEY);
        @endphp
        <div class="flex flex-wrap items-start -mx-2 min-w-0 lg:col-start-1 lg:row-start-1" autocomplete="off" data-form-column="main">
            @foreach ($leadKey === null ? [] : [$leadKey => $mainFields[$leadKey]] as $key => $field)
                @checkCondition($model, $field, $form)
                    <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" :mode="$mode"
                        wire:key="resource-field-{{ $key }}" />
                @endcheckCondition
            @endforeach
        </div>

        <aside class="flex flex-wrap items-start -mx-2 min-w-0 lg:col-start-2 lg:row-span-2 lg:row-start-1" data-form-column="rail" data-form-rail>
            @foreach ($formSections['fields'] as $key => $field)
                @if ($isRailPanel($field))
                    @checkCondition($model, $field, $form)
                        <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" :mode="$mode"
                            wire:key="resource-field-{{ $key }}" />
                    @endcheckCondition
                @endif
            @endforeach
        </aside>

        @if ($restFields !== [])
            <div class="flex flex-wrap items-start -mx-2 min-w-0 lg:col-start-1 lg:row-start-2" autocomplete="off" data-form-column="main-rest">
                @foreach ($restFields as $key => $field)
                    @checkCondition($model, $field, $form)
                        <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" :mode="$mode"
                            wire:key="resource-field-{{ $key }}" />
                    @endcheckCondition
                @endforeach
            </div>
        @endif
    @elseif ($formSections['card'] && ! $inModal)
        {{-- Plain inputs without a panel get the card a panel would give them (fields/panel). --}}
        <div class="flex flex-wrap items-start -mx-2" autocomplete="off">
            <div class="px-2 w-full" data-form-card>
                <div class="mb-8 aura-card">
                    <div class="mb-2">
                        <div class="flex flex-wrap items-start -mx-2 w-[calc(100%+1rem)]">
                            @foreach ($formSections['fields'] as $key => $field)
                                @checkCondition($model, $field, $form)
                                    <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" :mode="$mode"
                                        wire:key="resource-field-{{ $key }}" />
                                @endcheckCondition
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="flex flex-wrap items-start -mx-2" autocomplete="off">
            @foreach ($formSections['fields'] as $key => $field)
                @checkCondition($model, $field, $form)
                    <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" :mode="$mode"
                        wire:key="resource-field-{{ $key }}" />
                @endcheckCondition
            @endforeach
        </div>
    @endif


    <x-aura::validation-errors />
</div>
