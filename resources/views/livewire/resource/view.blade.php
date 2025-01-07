<div wire:key="resource-view-{{ $model->id }}">
    @section('title', __('View '. $model->singularName()))

    @if(!$inModal)
    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-6 h-6 mr-0" />
        @if(Route::has('aura.' . $model->getSlug() . '.index'))
            <x-aura::breadcrumbs.li :href="route('aura.' . $model->getSlug() . '.index')" :title="__(Str::plural($slug))" />
        @else
            <x-aura::breadcrumbs.li :title="__(Str::plural($slug))" />
        @endif
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
            <div class="flex flex-wrap items-start -mx-2" wire:key="resource-view-fields">
                @foreach($this->viewFields as $key => $field)
                <x-aura::fields.conditions :field="$field" :model="$model" wire:key="resource-field-{{ $key }}">
                    <x-dynamic-component :component="$field['field']->view()" :field="$field" :form="$form" :mode="$mode" />
                </x-aura::fields.conditions>
                @endforeach
            </div>

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

</div>
