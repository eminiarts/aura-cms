<x-aura::fields.wrapper :field="$field" wire:ignore>
    <div x-data="{
        init() {
            let quill = new window.Quill(this.$refs.quill, { theme: 'snow' })
    
            quill.on('text-change', function() {
                $dispatch('input', quill.root.innerHTML);
            });
        },
    }" x-ref="quill" wire:model="form.fields.{{ optional($field)['slug'] }}">
        {!! $this->form['fields'][$field['slug']] ?? '' !!}
    </div>

</x-aura::fields.wrapper>

@assets
    @once
        @push('scripts')
            @vite(['resources/js/quill.js'], 'vendor/aura/libs')
        @endpush
    @endonce
@endassets