@php
    $post = $field['field']->resource($this->model, $field['resource'], $field['option']);
@endphp

<x-aura::fields.wrapper :field="$field">
    <div>
        @if ($post)
        <a href="{{ $post->editUrl() }}">{{ $post->title() }}</a>
    @else
        <span class="text-muted">No {{ $field['resource'] }} selected</span>
    @endif
    </div>
</x-aura::fields.wrapper>
