<x-aura::fields.wrapper :field="$field" wire:ignore>
    <div
        @if($field['disabled'] ?? false)
            class="bg-gray-100 rounded-md opacity-50 cursor-not-allowed"
        @endif
    >
    <div
        @if($field['disabled'] ?? false)
            class="pointer-events-none"
        @endif
    >

        <div x-data="{
            init() {
                let quill = new window.Quill(this.$refs.quill, {
                    theme: 'snow',
                    readOnly: @json($field['disabled'] ?? false)
                });

                quill.on('text-change', function() {
                    $wire.$set('form.fields.{{ optional($field)['slug'] }}', quill.root.innerHTML);
                });

                // Fix: Use a proper JavaScript expression for $watch
                $watch('disabled', value => {
                    quill.enable(!value);
                });
            },
            disabled: @json($field['disabled'] ?? false)
        }" x-ref="quill">
            {!! $this->form['fields'][$field['slug']] ?? '' !!}
        </div>
    </div>
    </div>
</x-aura::fields.wrapper>

@assets
    @vite(['resources/js/quill.js'], 'vendor/aura/libs')
@endassets
