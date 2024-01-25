<x-aura::layout.app>
    @php
        $dashboardComponent = config('aura.dashboard_component');
    @endphp

    @if($dashboardComponent && class_exists($dashboardComponent))
        @livewire($dashboardComponent)
    @else
        <p>No custom dashboard component defined.</p>
        {{-- @livewire(Eminiarts\Aura\Http\Livewire\Dashboard::class); --}}
    @endif

</x-aura::layout.app>
