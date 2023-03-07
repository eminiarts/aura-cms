<x-aura::fields.wrapper :field="$field">
    {{-- {{ $this->post['fields'][$field['slug']] }} --}}
    {!! $this->model->display($field['slug']) !!}
</x-aura::fields.wrapper>
