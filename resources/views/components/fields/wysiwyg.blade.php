<x-aura::fields.wrapper :field="$field" wire:ignore>
    <div x-data="{
        init() {
            let quill = new window.Quill(this.$refs.quill, { theme: 'snow' })

            quill.on('text-change', function() {
                $wire.$set('form.fields.{{ optional($field)['slug'] }}', quill.root.innerHTML);
            });
        },
    }" x-ref="quill">
        {!! $this->form['fields'][$field['slug']] ?? '' !!}
    </div>

</x-aura::fields.wrapper>


    @once
        @push('scripts')
            @vite(['resources/js/quill.js'], 'vendor/aura/libs')
        @endpush
    @endonce
