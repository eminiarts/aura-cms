<div class="">
    @section('title', 'Edit '. $model->singularName() . ' • ' . $model->name . ' • ')
    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-7 h-7 mr-0" />
        <x-aura::breadcrumbs.li :href="route('aura.taxonomy.index', $slug)" :title="Str::plural($slug)" />
        <x-aura::breadcrumbs.li :title="$model->name" />
    </x-aura::breadcrumbs.li>

     <div class="grid gap-6 aura-edit-post-container sm:grid-cols-3">
        <div class="col-span-1 sm:col-span-2 ">

            @foreach($this->editFields as $key => $field)
            <div wire:key="post-field-{{ $key }}" id="post-field-{{ optional($field)['slug'] }}-wrapper">
                <style>
                #post-field-{{ optional($field)['slug'] }}-wrapper {
                    width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
                }

                @media screen and (max-width: 768px) {
                    #post-field-{{ optional($field)['slug'] }}-wrapper {
                    width: 100%;
                    }
                }
                </style>
                <x-dynamic-component :component="$field['field']->component" :field="$field" />
            </div>
            @endforeach

            @if (count($errors->all()))
            <div class="block">
                <div class="mt-8 form_errors">
                    <strong class="block text-red-600">Unfortunately, there were still the following validation
                        errors:</strong>
                    <div class="prose text-red-600">
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

        <div class="col-span-1">
            <div class="aura-card">
                <x-button size="xl" wire:click="save">Save</x-button>
            </div>
        </div>
    </div>
</div>
