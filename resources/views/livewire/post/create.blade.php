<div class="">
    @section('title', 'Create '. $model->singularName() . ' • ')

    @if(!$inModal)
    <x-breadcrumbs>
        <x-breadcrumbs.li :href="route('dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-7 h-7 mr-0" />
        <x-breadcrumbs.li :href="route('aura.post.index', $slug)" :title="Str::plural($slug)" />
        <x-breadcrumbs.li title="Create {{ $model->singularName() }}" />
    </x-breadcrumbs>
    @endif

    <div class="flex items-center justify-between my-8">
        <div>
            <h1 class="text-3xl font-semibold">Create {{ $model->singularName() }}</h1>
        </div>

        <div>
            <x-button size="lg" wire:click="save">
                <div wire:loading>
                    <x-icon.loading />
                </div>
                Save
            </x-button>
        </div>
    </div>

    @if($model::usesTitle())
    <div class="mb-4">
        <x-fields.wrapper :field="['slug' => 'title']" wrapperClass="" class="-mx-4">
            <x-input.text wire:model.defer="post.title" error="post.title" placeholder="Title"></x-input.text>
        </x-fields.wrapper>
    </div>
    @endif

    <div class="grid gap-6 aura-edit-post-container sm:grid-cols-3">
        <div class="col-span-1 sm:col-span-3">

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

           @foreach($this->editFields as $key => $field)
            <x-fields.conditions :field="$field" :model="$model">
                <div wire:key="post-field-{{ $key }}">
                    <x-dynamic-component :component="$field['field']->component" :field="$field" />
                </div>
            </x-fields.conditions>
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

        {{-- <div class="col-span-1">
            <div class="aura-card">
                <x-button size="xl" wire:click="save">Save</x-button>
            </div>
        </div> --}}
    </div>
</div>
