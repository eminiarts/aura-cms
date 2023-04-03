@props([
    'widgets' => [],
])

{{-- @dd($widgets) --}}
@dump($widgets)

<div {{ $attributes->class(['flex flex-wrap mt-4 -mx-2']) }}>
    @foreach ($widgets as $widget)
        {{-- Conditions --}}
        1
        @livewire(\Livewire\Livewire::getAlias(get_class($widget['widget']), $widget['widget']))
    @endforeach
</div>
