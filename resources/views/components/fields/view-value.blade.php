<x-aura::fields.wrapper :field="$field">
    <div class="truncate">
        {!! $this->model->display($field['slug']) !!}
    </div>
</x-aura::fields.wrapper>
