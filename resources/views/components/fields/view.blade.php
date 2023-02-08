{{-- if $field['view'] exists as a view, include it with blade --}}
@if (View::exists($field['view']))
    @include($field['view'])
@else
    {{-- if $field['view'] exists as a component, include it with livewire --}}
    <x-aura::dynamic-component :component="$field['view']" :field="$field" :model="$this->model" />
@endif


