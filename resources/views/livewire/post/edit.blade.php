<div>
    @section('title', __('Edit ' . $model->singularName()))

    @if(!$inModal)
    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard"
            iconClass="text-gray-500 w-7 h-7 mr-0" />
        <x-aura::breadcrumbs.li :href="route('aura.post.index', $slug)" :title="__(Str::plural($slug))" />
        <x-aura::breadcrumbs.li :title="$model->title" />
    </x-aura::breadcrumbs>
    @endif

    <div class="flex items-center justify-between my-8">
        <div>
            <h1 class="text-3xl font-semibold">{{ __('Edit ' . $model->singularName()) }}</h1>
        </div>

        <div class="flex items-center space-x-2">
    
    @include('aura::livewire.post.actions')

    {{-- If the $model is an instance of User Resource, add a button to impersonate the user --}}
    @if ($model instanceof Eminiarts\Aura\Resources\User)
    <x-aura::button.transparent :href="route('impersonate', $model->id)">
        <x-slot:icon>
            <x-aura::icon class="w-5 h-5 mr-2" icon="user-impersonate" />
        </x-slot:icon>
        {{ __('Impersonate') }}
    </x-aura::button.transparent>
    @endif

    <a href="{{ route('aura.post.view', [$slug, $model->id]) }}" class="text-gray-500 hover:text-gray-700">
        <x-aura::button.transparent>
            <x-aura::icon.view class="w-5 h-5 mr-2" />
            {{ __('View') }}
        </x-aura::button.transparent>
    </a>

    <x-aura::button size="lg" wire:click="save">
        <div wire:loading wire:target="save">
            <x-aura::icon.loading />
        </div>
        {{ __('Save') }}
    </x-aura::button>
</div>

    </div>

    @if($model::usesTitle())
    <div class="mb-4">
        <x-aura::fields.wrapper :field="['slug' => 'title']" wrapperClass="" class="-mx-4">
            <x-aura::input.text label="Title" wire:model="post.title" error="post.title" placeholder="Title">
            </x-aura::input.text>
        </x-aura::fields.wrapper>
    </div>
    @endif

    <div class="grid gap-6 mt-4 aura-edit-post-container sm:grid-cols-3" x-data="{
    model: @entangle('post'),
    init() {
        console.log('init post edit', this.model);
    }
}">

        <div class="col-span-1 mx-0 sm:col-span-3">

            {{-- @dump($this->editFields) --}}
            
            <div class="flex flex-wrap items-start -mx-2">
                @foreach($this->editFields as $key => $field)
                @checkCondition($post['fields'], $field)
                    <x-dynamic-component :component="$field['field']->component" :field="$field" wire:key="post-field-{{ $key }}" />
                @endcheckCondition
                @endforeach
            </div>

            @if (count($errors->all()))
            <div class="block">
                <div class="mt-8 form_errors">
                    <strong class="block text-red-600">
                        {{ __('Unfortunately, there were still the following validation errors:') }}
                    </strong>
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
