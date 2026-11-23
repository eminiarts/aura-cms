@php
    $specimens = $this->fieldSpecimens();
    $live = collect($specimens)->flatten(1)->map(fn ($field) => class_basename($field['type']))->all();
    $rules = [
        [__('One wrapper'), __('Every field is fields/wrapper: label (14 px medium, * when required), help as a tooltip beside it, the control, then the error. Nothing else decides spacing.')],
        [__('One control'), __('Text, choice and date fields reuse the form controls: .aura-field, 36 px base height, --aura-control-radius. A field never sets its own height, ring or corners.')],
        [__('Width by layout'), __('A field sets its width with style.width (25, 33, 50, 100 %). Neighbours sit --aura-layout-gap apart; a row of fields starts at the panel padding.')],
        [__('Read-only is text'), __('On view pages and in tables a field shows its value as text (fields/view-value, *-index), not a disabled control.')],
    ];
@endphp

{{-- Live specimens: the real fields/* views, bound to $form like a resource form. --}}
@foreach ($specimens as $group => $fields)
    <div class="mt-6 aura-card" data-field-group="{{ \Illuminate\Support\Str::slug($group) }}">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ $group }}</span>
        <div class="mt-3 flex flex-wrap items-start aura-layout-row">
            @foreach ($fields as $field)
                <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" />
            @endforeach
        </div>
    </div>
@endforeach

{{-- Rules every field follows. --}}
<div class="grid gap-4 mt-4 md:grid-cols-2 xl:grid-cols-4" data-field-rules>
    @foreach ($rules as [$title, $text])
        <div class="aura-card">
            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $title }}</div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $text }}</p>
        </div>
    @endforeach
</div>

{{-- Catalogue: every field type, the control it renders, live specimen marked. --}}
<div class="mt-4 aura-card" data-field-catalogue>
    <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('All field types') }}</span>
    <div class="grid gap-x-8 gap-y-6 mt-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->fieldCatalogue() as $group => $types)
            <div>
                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $group }}</div>
                <dl class="mt-2 space-y-1">
                    @foreach ($types as $type => $control)
                        <div class="grid grid-cols-[9rem_1fr] gap-3 text-sm">
                            <dt class="text-gray-700 dark:text-gray-200">
                                {{ $type }}
                                @if (in_array($type, $live, true))
                                    <span class="ml-1 inline-block size-1.5 align-middle rounded-full bg-primary-500" title="{{ __('Live specimen above') }}"></span>
                                @endif
                            </dt>
                            <dd class="text-gray-500 truncate dark:text-gray-400">{{ $control }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endforeach
    </div>
    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
        <span class="inline-block size-1.5 align-middle rounded-full bg-primary-500"></span>
        {{ __('has a live specimen above. Relations, media and layout fields need a saved record; see them on a resource form.') }}
    </p>
</div>
