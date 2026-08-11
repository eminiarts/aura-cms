<div class="flex items-center justify-between my-8 {{ $recordLayout->hasPanels() ? 'flex-wrap gap-4' : '' }}">
    <div>
        @yield('view-header')
        <h1 class="text-2xl font-semibold">
            {{ __('View :resource', ['resource' => __($model->singularName())]) }}
        </h1>
    </div>

    <div class="flex items-center {{ $recordLayout->hasPanels() ? 'flex-wrap gap-2' : 'space-x-2' }}">
        @if ($recordLayout->panels(\Aura\Base\RecordLayout\RecordLayoutRegion::HeaderActions) !== [])
            <div class="flex flex-wrap items-center gap-2" data-record-layout-region="header-actions">
                @foreach ($recordLayout->panels(\Aura\Base\RecordLayout\RecordLayoutRegion::HeaderActions) as $registeredPanel)
                    @include('aura::livewire.resource.record-layout.panel', ['registeredPanel' => $registeredPanel])
                @endforeach
            </div>
        @endif

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
