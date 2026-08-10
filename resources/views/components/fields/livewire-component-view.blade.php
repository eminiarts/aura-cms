@props(['field'])

@php
    $definition = app(\Aura\Base\Services\EmbeddedComponentResolver::class)->resolve(
        field: $field,
        resource: $this->model ?? null,
        surface: \Aura\Base\Services\EmbeddedComponentSurface::View,
    );
@endphp

@if ($definition)
    <x-aura::fields.wrapper :field="$field" wrapper-class="px-2" :show-label="false">
        @livewire($definition->alias, $definition->parameters, key($definition->key))
    </x-aura::fields.wrapper>
@endif
