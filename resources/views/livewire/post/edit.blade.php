<div>
    @section('title', 'Edit '. $model->singularName() . ' • ' . $model->title)

    @if(!$inModal)
    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard"
            iconClass="text-gray-500 w-7 h-7 mr-0" />
        <x-aura::breadcrumbs.li :href="route('aura.post.index', $slug)" :title="Str::plural($slug)" />
        <x-aura::breadcrumbs.li :title="$model->title" />
    </x-aura::breadcrumbs>
    @endif

    <div class="flex items-center justify-between my-8">
        <div>
            <h1 class="text-3xl font-semibold">Edit {{ $model->singularName() }}</h1>
        </div>

        @include('aura::livewire.post.actions')
    </div>

    @if($model::usesTitle())
    <div class="mb-4">
        <x-aura::fields.wrapper :field="['slug' => 'title']" wrapperClass="" class="-mx-4">
            <x-aura::input.text label="Title" wire:model.defer="post.title" error="post.title" placeholder="Title">
            </x-aura::input.text>
        </x-aura::fields.wrapper>
    </div>
    @endif

    <div class="grid gap-6 mt-4 aura-edit-post-container sm:grid-cols-3" x-data="{
    model: @entangle('post').defer,
    init() {
        console.log('init post edit', this.model);
    }
}">

        <div class="col-span-1 mx-0 sm:col-span-3">

            {{-- @dump($this->fields)
            @dump($this->post) --}}
            <div class="flex flex-wrap items-start -mx-2">
                @foreach($this->editFields as $key => $field)
                <x-aura::fields.conditions :field="$field" :model="$model" wire:key="post-field-{{ $key }}">
                    <x-dynamic-component :component="$field['field']->component" :field="$field" />
                </x-aura::fields.conditions>
                @endforeach
            </div>

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

    </div>

</div>
