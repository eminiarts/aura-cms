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
        // Record page: details card on the left, embedded record lists on the right from xl up
        // (below xl, next to the app sidebar, the lists would be too narrow for their columns).
        // Modals and pages with record-layout side panels keep the single column.
        $sections = \Aura\Base\RecordLayout\RecordViewSections::split($this->viewFields, $model);
        $splitRelated = ! $inModal && ($splitRelated ?? true) && $sections['related'] !== [];
    @endphp

    <div class="col-span-1 mx-0 min-w-0 sm:col-span-3 {{ $splitRelated ? 'aura-record-columns xl:grid xl:grid-cols-[minmax(0,336px)_minmax(0,1fr)] xl:gap-6 xl:items-start 2xl:grid-cols-[minmax(0,380px)_minmax(0,1fr)] 2xl:gap-8' : '' }}">
        @if ($sections['details'] !== [])
        <div class="flex flex-wrap items-start -mx-2 {{ $splitRelated ? 'aura-record-details' : '' }}" wire:key="resource-view-fields">
            @foreach($sections['details'] as $key => $field)
            <x-aura::fields.conditions :field="$field" :model="$model" wire:key="resource-field-{{ $key }}">
                <x-dynamic-component :component="$field['field']->view()" :field="$field" :form="$form" :mode="$mode" />
            </x-aura::fields.conditions>
            @endforeach
        </div>
        @endif

        @if ($sections['related'] !== [])
        {{-- Related records as unboxed lists; the has-many view brings its own px-4, which -mx-4 cancels so the lists align with the column. --}}
        <div class="space-y-8 min-w-0 {{ $splitRelated ? '' : 'mt-2' }}" wire:key="resource-view-related" data-record-related>
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
