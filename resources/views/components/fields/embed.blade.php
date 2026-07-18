<x-aura::fields.wrapper :field="$field">
    <embed type="{{ $this->model->mime_type }}" src="{{ method_exists($this->model, 'path') ? $this->model->path() : '/storage/'.$this->model->url }}" class="w-full">
</x-aura::fields.wrapper>
