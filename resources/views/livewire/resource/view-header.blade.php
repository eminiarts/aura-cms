<div class="flex items-center justify-between gap-4 my-8 {{ $recordLayout->hasPanels() ? 'flex-wrap' : '' }}">
    <div class="min-w-0">
        @yield('view-header')
        {{-- The record is the subject of the page: resource name as eyebrow, record title as H1. --}}
        <p class="aura-eyebrow">{{ __($model->singularName()) }}</p>
        <h1 class="mt-0.5 aura-page-title truncate">{{ $model->title() }}</h1>
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
