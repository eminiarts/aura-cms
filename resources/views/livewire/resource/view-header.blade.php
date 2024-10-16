<div class="flex items-center justify-between my-8">
    <div>
        @yield('view-header')
        <h1 class="text-xl font-semibold">
            {{ __('View ' . $model->singularName()) }}
        </h1>
    </div>

    <div class="flex items-center space-x-2">
        @include('aura::livewire.resource.actions')

        @can('update', $model)
            <x-aura::button href="{{ route('aura.resource.edit', [$slug, $model->id]) }}">
                {{ __('Edit') }}
            </x-aura::button>
        @endcan
    </div>
</div>
