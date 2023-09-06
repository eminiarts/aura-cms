<div class="flex items-center justify-between my-8">
    <div>
        @yield('view-header')
        <h1 class="text-3xl font-semibold">
            {{ __('View ' . $model->singularName()) }}
        </h1>
    </div>

    <div class="flex items-center space-x-2">
        @include('aura::livewire.post.actions')

        @can('update', $model)
        <a href="{{ route('aura.post.edit', [$slug, $model->id]) }}" class="text-gray-500 hover:text-gray-700">
            <x-aura::button size="lg">
                <x-aura::icon.edit class="w-5 h-5 mr-2" />
                {{ __('Edit') }}
            </x-aura::button>
        </a>
        @endcan
    </div>
</div>
