<div class="flex items-center justify-between my-8">
    <div>
        @yield('view-header')
        <h1 class="text-2xl font-semibold">
            {{ __('View :resource', ['resource' => __($model->singularName())]) }}
        </h1>
    </div>

    <div class="flex items-center space-x-2">
        @include('aura::livewire.resource.actions')

        @can('update', $model)
            @if(Route::has('aura.' . $slug . '.edit'))
                <x-aura::button href="{{ route('aura.' . $slug . '.edit', $model->id) }}">
                    {{ __('Edit') }}
                </x-aura::button>
            @endif
        @endcan
    </div>
</div>
