{{-- Actions sit to the right of the title, as on list pages; the title takes up to two lines beside them.
     Only when they leave the title less than 10rem (several actions on a phone) do they wrap below it. --}}
<div class="flex flex-wrap gap-x-4 gap-y-3 justify-between items-center mt-1 mb-6 sm:my-8 {{ $recordLayout->hasPanels() ? '' : 'sm:flex-nowrap' }}">
    <div class="flex flex-1 gap-3 items-center min-w-[10rem]">
        @yield('view-header')
        {{-- The record is the subject of the page: its avatar, resource name as eyebrow, record title as H1. --}}
        @if ($model->avatarShape())
            <x-aura::avatar :record="$model" size="lg" />
        @endif
        <div class="min-w-0">
            <p class="aura-eyebrow">{{ __($model->singularName()) }}</p>
            <h1 class="mt-0.5 break-words aura-page-title line-clamp-2 sm:line-clamp-1" title="{{ $model->title() }}">{{ $model->title() }}</h1>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 items-center shrink-0">
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
