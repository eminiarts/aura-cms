<div>
    @section('title', 'View '. $model->singularName() . ' • ' . $model->title . ' • ')

    @if(!$inModal)
    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-7 h-7 mr-0" />
        <x-aura::breadcrumbs.li :href="route('aura.post.index', $slug)" :title="Str::plural($slug)" />
        <x-aura::breadcrumbs.li :title="$model->title" />
    </x-aura::breadcrumbs>
    @endif

    <div class="flex items-center justify-between my-8">
        <div>
            <h1 class="text-3xl font-semibold">View {{ $model->singularName() }}</h1>
        </div>

        <div class="flex items-center space-x-2">
            {{-- <x-aura::button size="lg" wire:click="save">
                <div wire:loading>
                    <x-aura::icon.loading />
                </div>
                Save
            </x-aura::button> --}}
        </div>
    </div>

    @if($model::usesTitle())
    <div class="mb-4">
        <x-aura::fields.wrapper :field="['slug' => 'title']" wrapperClass="" class="-mx-4">
            <x-aura::input.text label="Title" wire:model.defer="post.title" error="post.title" placeholder="Title">
            </x-aura::input.text>
        </x-aura::fields.wrapper>
    </div>
    @endif

    {{-- @dump($post) --}}
    {{-- @dump($this->fields) --}}

    <style>
        .aura-view-post-container input {
            border: 0 !important;
            background-color: var(--gray-100)!important;
            pointer-events: none !important;
            box-shadow: none !important;
        }

    input[type="text"],
    input[type="email"],
    input[type="password"],
    input[type="url"],
    input[type="tel"],
    input[type="number"],
    textarea,
    select {
        background-color: var(--gray-100)!important;
        border-radius: 0.375rem;
        border: 1px solid var(--gray-200) !important;
        padding: 0.5rem;
        font-size: 1rem;
        line-height: 1.5;
        color: var(--gray-700);
    }
    </style>

    <div class="grid gap-6 mt-4 aura-view-post-container sm:grid-cols-3" x-data="{
         init() {
            const container = document.querySelector('.aura-view-post-container');
                        const inputs = container.querySelectorAll('input, select, textarea');


            inputs.forEach((input) => {
                input.setAttribute('readonly', true);
            });
        }
    }">

        <div class="col-span-1 mx-0 sm:col-span-3">

            @foreach($this->editFields as $key => $field)
            <x-aura::fields.conditions :field="$field" :model="$model">
                <div wire:key="post-field-{{ $key }}">
                    <x-dynamic-component :component="$field['field']->component" :field="$field" />
                </div>
            </x-aura::fields.conditions>
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

    </div>

</div>
