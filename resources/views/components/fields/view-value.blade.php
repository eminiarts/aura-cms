<x-aura::fields.wrapper :field="$field">
    {{ $this->post['fields'][$field['slug']] }}
</x-aura::fields.wrapper>
