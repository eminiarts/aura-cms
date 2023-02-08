@props([
    'widgets' => [],
])

<div {{ $attributes->class(['flex flex-wrap mt-4 -mx-2']) }}>
    @foreach ($widgets as $widget)
        @if ($widget::canView())
            @livewire(\Livewire\Livewire::getAlias(get_class($widget) ), $widget->settings)
        @endif
    @endforeach
</div>
