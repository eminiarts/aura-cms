<div class="flex items-center justify-between my-8">
    <div>
        {{ app('aura')::injectView('post_edit_title_before') }}
        <h1 class="text-3xl font-semibold">{{ __('Edit ' . $model->singularName()) }}</h1>
        {{ app('aura')::injectView('post_edit_title_after') }}
    </div>

    <div class="flex items-center space-x-2">

        @include('aura::livewire.resource.actions')

        {{-- If the $model is an instance of User Resource, add a button to impersonate the user --}}
        @if ($model instanceof Aura\Base\Resources\User )
            @canBeImpersonated($model, $guard = null)
            <x-aura::button.transparent :href="route('impersonate', $model->id)">
                <x-slot:icon>
                    <x-aura::icon class="w-5 h-5 mr-2" icon="user-impersonate"/>
                </x-slot:icon>
                {{ __('Impersonate') }}
            </x-aura::button.transparent>
            @endCanBeImpersonated
        @endif

        @can('view', $model)
            <a href="{{ route('aura.resource.view', [$slug, $model->id]) }}" class="text-gray-500 hover:text-gray-700">
                <x-aura::button.transparent>
                    <x-aura::icon.view class="w-5 h-5 mr-2"/>
                    {{ __('View') }}
                </x-aura::button.transparent>
            </a>
        @endcan

        <x-aura::button size="lg" wire:click="save" wire:loading.attr="disabled">
            <div wire:loading.delay wire:target="save">
                <x-aura::icon.loading/>
            </div>
            {{ __('Save') }}
        </x-aura::button>
    </div>

</div>
