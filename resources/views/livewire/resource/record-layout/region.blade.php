@props(['region'])

@foreach ($recordLayout->panels($region) as $registeredPanel)
    @include('aura::livewire.resource.record-layout.panel', ['registeredPanel' => $registeredPanel])
@endforeach
