<x-aura::fields.wrapper :field="$field">
    <embed type="{{ $this->model->mime_type }}" src="/storage/{{ $this->model->url }}" class="w-full">
</x-aura::fields.wrapper>
