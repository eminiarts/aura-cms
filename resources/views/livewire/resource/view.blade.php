<div>
    @section('title', __('View '. $model->singularName()))

    @if(!$inModal)
    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-7 h-7 mr-0" />
        <x-aura::breadcrumbs.li :href="route('aura.resource.index', $slug)" :title="__(Str::plural($slug))" />
        <x-aura::breadcrumbs.li :title="$model->title()" />
    </x-aura::breadcrumbs>
    @endif

    @include($model->viewHeaderView())

    @if($model::usesTitle())
    <div class="mb-4">
        <x-aura::fields.wrapper :field="['slug' => 'title']" wrapperClass="" class="-mx-4">
            <x-aura::input.text label="Title" wire:model="post.title" error="post.title" placeholder="Title">
            </x-aura::input.text>
        </x-aura::fields.wrapper>
    </div>
    @endif

    {{-- @dump($resource) --}}
    {{-- @dump($this->fields) --}}

    {{-- <style >
        .aura-view-post-container input, .aura-input {
            border: 0 !important;
            background-color: var(--gray-100)!important;
            /* pointer-events: none !important; */
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
    </style> --}}

    <div class="grid gap-6 mt-4 aura-view-post-container sm:grid-cols-3" x-data="{
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

        <div class="col-span-1 mx-0 sm:col-span-3">
            {{-- @dd($this->viewFields) --}}
            <div class="flex flex-wrap items-start -mx-2">
                @foreach($this->viewFields as $key => $field)
                <x-aura::fields.conditions :field="$field" :model="$model" wire:key="resource-field-{{ $key }}">
                    <x-dynamic-component :component="$field['field']->view()" :field="$field" :form="$form" />
                </x-aura::fields.conditions>
                @endforeach
            </div>

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
