<div>
    @section('title', 'Edit '. $model->singularName() . ' • ' . $model->title . ' • ')

    @if(!$inModal)
    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard"
            iconClass="text-gray-500 w-7 h-7 mr-0" />
        <x-aura::breadcrumbs.li :href="route('aura.taxonomy.index', $slug)" :title="Str::plural($slug)" />
        <x-aura::breadcrumbs.li :title="$model->title" />
    </x-aura::breadcrumbs>
    @endif

    <div class="flex justify-between items-center my-8">
        <div>
            <h1 class="text-3xl font-semibold">Edit {{ $model->singularName() }}</h1>
        </div>

        <div class="flex items-center space-x-2">
        <x-aura::dropdown width="w-96">
            <x-slot name="trigger">
                <x-aura::button.transparent>
                        <x-aura::icon.dots class="mr-2 w-5 h-5" />
                Actions
                </x-aura::button.transparent>
            </x-slot>
            <x-slot name="content">
                <div class="px-2">
                    @foreach($this->actions as $action => $label)
                    <div wire:click="singleAction('{{ $action }}')" class="p-2 cursor-pointer hover:bg-primary-100">
                        @if(is_array($label))
                           <div class="flex flex-col {{ $label['class'] ?? ''}}">
                            <div class="flex space-x-2">
                                 <div class="shrink-0">
                                    {!! $label['icon'] ?? '' !!}
                                 @if(optional($label)['icon-view'])
                                    @include($label['icon-view'])
                                 @endif
                                 </div>
                            <strong class="font-semibold">{{ $label['label'] ?? '' }}
                                @if(optional($label)['description'])
                            <span class="inline-block text-sm font-normal leading-tight text-gray-500">{{ $label['description'] ?? '' }}</span>
                            @endif
                            </strong>
                            </div>

                           </div>
                        @else
                            {{ $label }}
                        @endif
                    </div>
                    @endforeach
                </div>
            </x-slot>
        </x-aura::dropdown>
            {{-- If the $model is an instance of User Resource, add a button to impersonate the user --}}
            @if ($model instanceof Eminiarts\Aura\Resources\User)
            <x-aura::button.transparent :href="route('impersonate', $model->id)" :id="$model->id">
                <x-slot:icon>
                    <x-aura::icon class="mr-2 w-5 h-5" icon="user-impersonate" />
                </x-slot:icon>
                Impersonate
            </x-aura::button.transparent>
            @endif
            <a href="{{ route('aura.taxonomy.view', [$slug, $model->id]) }}" class="text-gray-500 hover:text-gray-700">
                <x-aura::button.transparent size="lg">
                    <x-aura::icon.view class="mr-2 w-5 h-5" />
                    {{ __('View') }}
                </x-aura::button.transparent>
            </a>

            <x-aura::button size="lg" wire:click="save">
                <div wire:loading>
                    <x-aura::icon.loading />
                </div>
                {{ __('Save') }}
            </x-aura::button>
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

    <div class="grid gap-6 mt-4 aura-edit-post-container sm:grid-cols-3" x-data="{
    model: @entangle('post').defer,
    init() {
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
