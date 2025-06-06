<x-dynamic-component :component="config('aura.views.layout')">
    @php
        $dashboardComponent = config('aura.components.dashboard');
    @endphp

    @if($dashboardComponent && class_exists($dashboardComponent))
        @livewire($dashboardComponent)
    @else
        <p>No custom dashboard component defined.</p>
        {{-- @livewire(Aura\Base\Livewire\Dashboard::class); --}}
    @endif

</x-dynamic-component>
