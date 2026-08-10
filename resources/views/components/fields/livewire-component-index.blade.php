@props(['field', 'row', 'value' => null])

@php
    $definition = app(\Aura\Base\Services\EmbeddedComponentResolver::class)->resolve(
        field: $field,
        resource: $row,
        surface: \Aura\Base\Services\EmbeddedComponentSurface::Index,
        value: $value,
    );
@endphp

@if ($definition)
    @livewire($definition->alias, $definition->parameters, key($definition->key))
@endif
